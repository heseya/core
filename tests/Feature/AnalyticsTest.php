<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testPaymentsInRange($user): void
    {
        $this->{$user}->givePermissionTo('analytics.payments');
        $from = Carbon::today()->subHour();
        $to = Carbon::tomorrow();

        /** @var Order $orderBefore */
        $orderBefore = Order::factory()->create([
            'created_at' => Carbon::today()->subHours(2),
            'summary' => 150.0,
            'paid' => true,
        ]);

        $orderBefore->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $orderBefore->summary,
            'created_at' => $orderBefore->created_at,
            'currency' => $orderBefore->currency,
        ]));

        $order = Order::factory()->create([
            'paid' => true,
            'summary' => 1000.0,
        ]);

        $order->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $order->summary,
            'created_at' => $order->created_at,
            'currency' => $order->currency,
        ]));

        $orderAfter = Order::factory()->create([
            'created_at' => Carbon::tomorrow()->addHours(2),
            'paid' => true,
            'summary' => 250.0,
        ]);

        $orderAfter->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $orderAfter->summary,
            'created_at' => $orderAfter->created_at,
            'currency' => $orderAfter->currency,
        ]));

        $response = $this->actingAs($this->{$user})->json('GET', '/analytics/payments', [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'group' => 'total',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'amount' => '1000.00',
                'count' => 1,
                'currency' => $order->currency,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testPaymentsInRangeDoublePayment($user): void
    {
        $this->{$user}->givePermissionTo('analytics.payments');
        $from = Carbon::today()->subHour();
        $to = Carbon::tomorrow();

        $orderBefore = Order::factory()->create([
            'created_at' => Carbon::today()->subHours(2),
            'summary' => 150.0,
            'paid' => true,
        ]);

        $orderBefore->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $orderBefore->summary,
            'created_at' => $orderBefore->created_at,
            'currency' => $orderBefore->currency,
        ]));

        $order = Order::factory()->create([
            'paid' => true,
            'summary' => 1000.0,
        ]);

        $order->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $order->summary / 2,
            'created_at' => $order->created_at,
            'currency' => $order->currency,
        ]));

        $order->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $order->summary / 2,
            'created_at' => $order->created_at,
            'currency' => $order->currency,
        ]));

        $orderAfter = Order::factory()->create([
            'created_at' => Carbon::tomorrow()->addHours(2),
            'paid' => true,
            'summary' => 250.0,
        ]);

        $orderAfter->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $orderAfter->summary,
            'created_at' => $orderAfter->created_at,
            'currency' => $orderAfter->currency,
        ]));

        $response = $this->actingAs($this->{$user})->json('GET', '/analytics/payments', [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'group' => 'total',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'amount' => '1000.00',
                'count' => 2,
                'currency' => $order->currency,
            ]);
    }
}
