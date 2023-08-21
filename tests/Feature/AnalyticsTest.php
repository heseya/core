<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    private Currency $currency;

    /**
     * @dataProvider authProvider
     */
    public function testPaymentsInRange($user): void
    {
        $this->currency = Currency::DEFAULT;

        $this->{$user}->givePermissionTo('analytics.payments');
        $from = Carbon::today()->subHour();
        $to = Carbon::tomorrow();

        /** @var Order $orderBefore */
        $orderBefore = Order::factory()->create([
            'created_at' => Carbon::today()->subHours(2),
            'summary' => Money::of(150.0, $this->currency->value),
            'paid' => true,
        ]);

        $orderBefore->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $orderBefore->summary->getAmount()->toFloat(),
            'created_at' => $orderBefore->created_at,
        ]));

        $order = Order::factory()->create([
            'paid' => true,
            'summary' => Money::of(1000.0, $this->currency->value),
        ]);

        $order->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $order->summary->getAmount()->toFloat(),
            'created_at' => $order->created_at,
        ]));

        $orderAfter = Order::factory()->create([
            'created_at' => Carbon::tomorrow()->addHours(2),
            'paid' => true,
            'summary' => Money::of(250.0, $this->currency->value),
        ]);

        $orderAfter->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $orderAfter->summary->getAmount()->toFloat(),
            'created_at' => $orderAfter->created_at,
        ]));

        $this->actingAs($this->{$user})->json('GET', '/analytics/payments', [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'group' => 'total',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'amount' => 1000.0,
                'count' => 1,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testPaymentsInRangeDoublePayment($user): void
    {
        $this->currency = Currency::DEFAULT;

        $this->{$user}->givePermissionTo('analytics.payments');
        $from = Carbon::today()->subHour();
        $to = Carbon::tomorrow();

        $orderBefore = Order::factory()->create([
            'created_at' => Carbon::today()->subHours(2),
            'summary' => Money::of(150.0, $this->currency->value),
            'paid' => true,
        ]);

        $orderBefore->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $orderBefore->summary->getAmount()->toFloat(),
            'created_at' => $orderBefore->created_at,
        ]));

        $order = Order::factory()->create([
            'paid' => true,
            'summary' => Money::of(1000.0, $this->currency->value),
        ]);

        $order->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $order->summary->dividedBy(2, RoundingMode::HALF_DOWN)->getAmount()->toFloat(),
            'created_at' => $order->created_at,
        ]));

        $order->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $order->summary->dividedBy(2)->getAmount()->toFloat(),
            'created_at' => $order->created_at,
        ]));

        $orderAfter = Order::factory()->create([
            'created_at' => Carbon::tomorrow()->addHours(2),
            'paid' => true,
            'summary' => Money::of(250.0, $this->currency->value),
        ]);

        $orderAfter->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $orderAfter->summary->getAmount()->toFloat(),
            'created_at' => $orderAfter->created_at,
        ]));

        $this->actingAs($this->{$user})->json('GET', '/analytics/payments', [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'group' => 'total',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'amount' => 1000.0,
                'count' => 2,
            ]);
    }
}
