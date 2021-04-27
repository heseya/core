<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated as OrderStatusUpdatedEvent;
use App\Notifications\OrderCreated;

class OrderStatusUpdatedListener
{
    public $tries = 3;

    public function handle(OrderStatusUpdatedEvent $event): void
    {
        $order = $event->getOrder();

        $order->notify(new OrderCreated($order));
    }
}
