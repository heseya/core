<?php

namespace App\Listeners;

use App\Events\OrderUpdatedStatus as OrderStatusUpdatedEvent;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderUpdatedStatusListener
{
    public function handle(OrderStatusUpdatedEvent $event): void
    {
        $order = $event->getOrder();

        try {
            if (!$order->status?->no_notifications) {
                $order->notify(new OrderStatusUpdated($order));
            }
        } catch (Throwable) {
            Log::error("Couldn't send order update to the address: {$order->email}");
        }
    }
}
