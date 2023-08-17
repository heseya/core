<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\AnalyticsService;
use App\Services\Contracts\AnalyticsServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $analyticsService;

    public function setUp(): void
    {
        parent::setUp();

        $this->analyticsService = App::make(AnalyticsServiceContract::class);
    }

    public function testGetPaymentsOverPeriod(): void
    {
        $to = Carbon::today();
        $from = $to->copy()->subDays(30);

        $order = Order::factory()->create();

        $before = Payment::factory([
            'status' => PaymentStatus::SUCCESSFUL,
            'created_at' => $from->copy()->subDay(),
        ])->make();

        $onStart = Payment::factory([
            'status' => PaymentStatus::SUCCESSFUL,
            'created_at' => $from,
        ])->make();

        $during = Payment::factory([
            'status' => PaymentStatus::SUCCESSFUL,
            'created_at' => $from->copy()->addDays(15),
        ])->make();

        $onEnd = Payment::factory([
            'status' => PaymentStatus::SUCCESSFUL,
            'created_at' => $to->copy(),
        ])->make();

        $after = Payment::factory([
            'status' => PaymentStatus::SUCCESSFUL,
            'created_at' => $to->copy()->addDay(),
        ])->make();

        $order->payments()->save($before);
        $order->payments()->save($onStart);
        $order->payments()->save($during);
        $order->payments()->save($onEnd);
        $order->payments()->save($after);

        $amount = $onStart->amount + $during->amount + $onEnd->amount;

        $this->assertEquals([
            'total' => [
                'amount' => $amount,
                'count' => 3,
            ],
        ], $this->analyticsService->getPaymentsOverPeriod($from, $to, 'total'));
    }

    public function testGetPaymentsOverPeriodYearly(): void
    {
        $from = Carbon::parse('2020-01-01');
        $to = Carbon::parse('2021-12-31');

        $this->testGetPaymentsOverPeriodGroup(
            $from,
            $from->copy()->addMonth(),
            $to->copy()->subMonth(),
            $to,
            '2020',
            '2021',
            'yearly',
        );
    }

    public function testGetPaymentsOverPeriodMonthly(): void
    {
        $from = Carbon::parse('2020-01-01');
        $to = Carbon::parse('2020-02-29');

        $this->testGetPaymentsOverPeriodGroup(
            $from,
            $from->copy()->addDay(),
            $to->copy()->subDay(),
            $to,
            '2020-01',
            '2020-02',
            'monthly',
        );
    }

    public function testGetPaymentsOverPeriodDaily(): void
    {
        $from = Carbon::parse('2020-01-01 00:00');
        $to = Carbon::parse('2020-01-02 23:59');

        $this->testGetPaymentsOverPeriodGroup(
            $from,
            $from->copy()->addHour(),
            $to->copy()->subHour(),
            $to,
            '2020-01-01',
            '2020-01-02',
            'daily',
        );
    }

    public function testGetPaymentsOverPeriodHourly(): void
    {
        $from = Carbon::parse('2020-01-01 00:00');
        $to = Carbon::parse('2020-01-01 01:59');

        $this->testGetPaymentsOverPeriodGroup(
            $from,
            $from->copy()->addMinute(),
            $to->copy()->subMinute(),
            $to,
            '2020-01-01 00',
            '2020-01-01 01',
            'hourly',
        );
    }

    private function testGetPaymentsOverPeriodGroup(
        Carbon $groupOne0,
        Carbon $groupOne1,
        Carbon $groupTwo0,
        Carbon $groupTwo1,
        string $labelOne,
        string $labelTwo,
        string $group
    ): void {
        $order = Order::factory()->create();

        $oneG0 = Payment::factory([
            'status' => PaymentStatus::SUCCESSFUL,
            'created_at' => $groupOne0,
        ])->make();

        $twoG0 = Payment::factory([
            'status' => PaymentStatus::SUCCESSFUL,
            'created_at' => $groupOne1,
        ])->make();

        $oneG1 = Payment::factory([
            'status' => PaymentStatus::SUCCESSFUL,
            'created_at' => $groupTwo0,
        ])->make();

        $twoG1 = Payment::factory([
            'status' => PaymentStatus::SUCCESSFUL,
            'created_at' => $groupTwo1,
        ])->make();

        $order->payments()->save($oneG0);
        $order->payments()->save($twoG0);
        $order->payments()->save($oneG1);
        $order->payments()->save($twoG1);

        $amountG0 = $oneG0->amount + $twoG0->amount;
        $amountG1 = $oneG1->amount + $twoG1->amount;

        $this->assertEquals([
            $labelOne => [
                'amount' => $amountG0,
                'count' => 2,
            ],
            $labelTwo => [
                'amount' => $amountG1,
                'count' => 2,
            ],
        ], $this->analyticsService->getPaymentsOverPeriod($groupOne0, $groupTwo1, $group));
    }
}
