<?php

namespace App\Listeners;

use App\Events\OrderUpdatedStatus as OrderStatusUpdatedEvent;
use App\Notifications\OrderStatusUpdated;

class OrderUpdatedStatusListener
{
    public function handle(OrderStatusUpdatedEvent $event): void
    {
        $order = $event->getOrder();

        $order->notify(new OrderStatusUpdated($order));
    }
}
