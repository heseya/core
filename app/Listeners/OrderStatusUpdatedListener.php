<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated as OrderStatusUpdatedEvent;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class OrderStatusUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public function handle(OrderStatusUpdatedEvent $event): void
    {
        $order = $event->getOder();

        Notification::route('mail', $order->email)
            ->notify(new OrderStatusUpdated($order));
    }
}
