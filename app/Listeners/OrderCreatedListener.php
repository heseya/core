<?php

namespace App\Listeners;

use App\Events\OrderCreated as OrderCreatedEvent;
use App\Notifications\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class OrderCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderCreatedEvent $event): void
    {
        $order = $event->getOder();

        Notification::route('mail', $order->email)
            ->notify(new OrderCreated($order));
    }
}
