<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderUpdatedPaid;
use App\Mail\OrderPaid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class OrderPaidListener
{
    public function handle(OrderUpdatedPaid $event): void
    {
        $order = $event->getOrder();

        try {
            if ($order->paid) {
                Mail::to($order->email)
                    ->locale($order->locale)
                    ->send(new OrderPaid($order));
            }
        } catch (Throwable) {
            Log::error("Couldn't send order {$order->code}  paid notification to the address: {$order->email}");
        }
    }
}
