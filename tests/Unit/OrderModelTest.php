<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    public function testOverpaid(): void
    {
        $order = Order::factory()->create();

        $order->payments()->save(Payment::factory()->make([
            'amount' => $order->summary * 2,
            'payed' => true,
        ]));

        $this->assertTrue($order->isPayed());
    }
}
