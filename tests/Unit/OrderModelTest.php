<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    public function testOverpaid(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create();

        $order->payments()->save(Payment::factory()->make([
            'amount' => $order->summary->multipliedBy(2)->getAmount()->toFloat(),
            'status' => PaymentStatus::SUCCESSFUL,
            'currency' => $order->currency,
        ]));

        $order->refresh();
        $this->assertTrue($order->paid);
    }
}
