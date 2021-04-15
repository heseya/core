<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Contracts\AnalyticsServiceContract;
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

    public function testGetTotalOrderRevenue(): void
    {
        Product::factory()->count(3)->create();

        $orders = Order::factory()->count(2)->create()->each(function ($order) {
            $order->products()->saveMany(
                OrderProduct::factory()->count(3)->make(),
            );

            $order->payments()->save(
                Payment::factory([
                    'payed' => true,
                    'amount' => $order->summary,
                ])->make(),
            );
        });

        $total = $orders->reduce(fn ($total, $order) => $total + $order->payedAmount, 0.0);

        $this->assertEquals($total, $this->analyticsService->getTotalOrderRevenue());
    }
}
