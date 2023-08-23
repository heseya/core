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

        $payment = Payment::factory()->make([
            'currency' => $order->currency,
            'status' => PaymentStatus::SUCCESSFUL,
            'amount' => $order->summary->multipliedBy(2)->getAmount()->toFloat(),
        ]);

        $order->payments()->save($payment);

        $order->refresh();
        $this->assertTrue($order->paid);
    }
}
