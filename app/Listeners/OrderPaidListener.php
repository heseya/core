<?php

namespace App\Listeners;

use App\Events\OrderUpdatedPaid;
use App\Notifications\OrderPaid;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderPaidListener
{
    public function handle(OrderUpdatedPaid $event): void
    {
        $order = $event->getOrder();

        try {
            if ($order->paid) {
                $order->notify(new OrderPaid($order));
            }
        } catch (Throwable) {
            Log::error("Couldn't send order paid to the address: {$order->email}");
        }
    }
}
