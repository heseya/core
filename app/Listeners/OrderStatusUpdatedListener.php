<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated as OrderStatusUpdatedEvent;
use App\Notifications\OrderStatusUpdated;

class OrderStatusUpdatedListener
{
    public function handle(OrderStatusUpdatedEvent $event): void
    {
        $order = $event->getOrder();

        $order->notify(new OrderStatusUpdated($order));
    }
}
