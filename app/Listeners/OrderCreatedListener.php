<?php

namespace App\Listeners;

use App\Events\OrderCreated as OrderCreatedEvent;
use App\Notifications\OrderCreated;

class OrderCreatedListener
{
    public function handle(OrderCreatedEvent $event): void
    {
        $order = $event->getOrder();

        $order->notify(new OrderCreated($order));
    }
}
