<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Contracts\AnalyticsServiceContract;
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
            'amount' => $orderBefore->summary,
            'created_at' => $orderBefore->created_at,
            'currency' => $orderBefore->currency,
        ]));

        $order = Order::factory()->create([
            'paid' => true,
            'summary' => Money::of(1000.0, $this->currency->value),
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
            'summary' => Money::of(250.0, $this->currency->value),
        ]);

        $orderAfter->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $orderAfter->summary,
            'created_at' => $orderAfter->created_at,
            'currency' => $orderAfter->currency,
        ]));

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/analytics/payments', [
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString(),
                'group' => 'total',
            ])
            ->assertOk()
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
            'amount' => $orderBefore->summary,
            'created_at' => $orderBefore->created_at,
            'currency' => $orderBefore->currency,
        ]));

        $order = Order::factory()->create([
            'paid' => true,
            'summary' => Money::of(1000.0, $this->currency->value),
        ]);

        $order->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $order->summary->dividedBy(2, RoundingMode::HALF_DOWN),
            'created_at' => $order->created_at,
            'currency' => $order->currency,
        ]));

        $order->payments()->save(Payment::factory()->make([
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $order->summary->dividedBy(2),
            'created_at' => $order->created_at,
            'currency' => $order->currency,
        ]));

        $orderAfter = Order::factory()->create([
            'created_at' => Carbon::tomorrow()->addHours(2),
            'paid' => true,
            'summary' => Money::of(250.0, $this->currency->value),
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

    public function testPaymentsUnauthorized(): void
    {
        $to = Carbon::today();
        $from = $to->copy()->subDays(30);

        $response = $this->getJson('/analytics/payments', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group' => 'total',
        ]);

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testPayments($auth): void
    {
        $this->{$auth}->givePermissionTo('analytics.payments');

        $to = Carbon::today();
        $from = $to->copy()->subDays(30);

        $this->mock(AnalyticsServiceContract::class, function ($mock): void {
            $mock->shouldReceive('getPaymentsOverPeriod')
                ->andReturn([
                    'total' => [
                        [
                            'amount' => 1000.0,
                            'count' => 7,
                            'currency' => Currency::DEFAULT->value,
                        ]
                    ],
                ]);
        });

        $response = $this->actingAs($this->{$auth})->getJson('/analytics/payments', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group' => 'total',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'total' => [
                        [
                            'amount' => 1000.0,
                            'count' => 7,
                            'currency' => Currency::DEFAULT->value,
                        ]
                    ],
                ],
            ]);
    }
}
