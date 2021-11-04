<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated as OrderStatusUpdatedEvent;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderStatusUpdatedListener
{
    public function handle(OrderStatusUpdatedEvent $event): void
    {
        $order = $event->getOrder();

        try {
            $order->notify(new OrderStatusUpdated($order));
        } catch (Throwable) {
            Log::error("Couldn't send order update to the address: {$order->email}");
        }
    }
}
