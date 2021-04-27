<?php

namespace App\Listeners;

use App\Events\OrderCreated as OrderCreatedEvent;
use App\Notifications\OrderCreated;
use Illuminate\Support\Facades\Notification;

class OrderCreatedListener
{
    public $tries = 3;

    public function handle(OrderCreatedEvent $event): void
    {
        $order = $event->getOder();

        Notification::route('mail', $order->email)
            ->notify(new OrderCreated($order));
    }
}
