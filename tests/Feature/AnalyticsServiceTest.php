<?php

namespace Tests\Feature;

use App\Model\Order;
use App\Model\OrderProduct;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->analyticsService = new AnalyticsService();
    }

    public function testGetTotalOrderRevenue(): void
    {
        $orders = Order::factory()->count(2)->each(function ($order) {
            $order->products()->saveMany(
                OrderProduct::factory()->count(3)->create(),
            );
        })->save();

        dd($orders);

        $total = $orders->reduce(fn ($total, $order) => $total + $order->payedAmount, 0.0);

        assertEquals($total, $this->analyticsService->getTotalOrderRevenue());
    }
}
