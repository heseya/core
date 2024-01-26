<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderUpdatedStatus as OrderStatusUpdatedEvent;
use App\Mail\OrderStatusUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class OrderUpdatedStatusListener
{
    public function handle(OrderStatusUpdatedEvent $event): void
    {
        $order = $event->getOrder();

        try {
            if (!$order->status?->no_notifications) {
                Mail::to($order->email)
                    ->locale($order->locale)
                    ->send(new OrderStatusUpdated($order));
            }
        } catch (Throwable) {
            Log::error("Couldn't send order {$order->code} update to the address: {$order->email}");
        }
    }
}
