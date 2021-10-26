<?php

namespace App\Listeners;

use App\Events\OrderCreated as OrderCreatedEvent;
use App\Notifications\OrderCreated;
use Throwable;

class OrderCreatedListener
{
    public function handle(OrderCreatedEvent $event): void
    {
        $order = $event->getOrder();

        try {
            $order->notify(new OrderCreated($order));
        } catch (Throwable) {
        }
    }
}
