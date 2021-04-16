<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Contracts\AnalyticsServiceContract;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->analyticsService = app(AnalyticsServiceContract::class);
    }

    public function testGetPaymentsOverPeriodTotal(): void
    {
        $to = Carbon::today();
        $from = $to->copy()->subDays(30);

        $order = Order::factory()->create();

        $before = Payment::factory([
            'payed' => true,
            'created_at' => $from->copy()->subDay(),
        ])->make();

        $onStart = Payment::factory([
            'payed' => true,
            'created_at' => $from,
        ])->make();

        $during = Payment::factory([
            'payed' => true,
            'created_at' => $from->copy()->addDays(15),
        ])->make();

        $onEnd = Payment::factory([
            'payed' => true,
            'created_at' => $to->copy()->addHours(5),
        ])->make();

        $after = Payment::factory([
            'payed' => true,
            'created_at' => $to->copy()->addDay(),
        ])->make();

        $order->payments()->save($before);
        $order->payments()->save($onStart);
        $order->payments()->save($during);
        $order->payments()->save($onEnd);
        $order->payments()->save($after);

        $amount = $onStart->amount + $during->amount + $onEnd->amount;

        $this->assertEquals([
            'amount' => $amount,
            'count' => 3,
        ], $this->analyticsService->getPaymentsOverPeriodTotal($from, $to));
    }
}
