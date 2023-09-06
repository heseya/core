<?php

namespace App\Listeners;

use App\Events\OrderCreated as OrderCreatedEvent;
use App\Models\User;
use App\Notifications\OrderCreated;
use App\Services\Contracts\SettingsServiceContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class OrderCreatedListener
{
    public function __construct(
        private SettingsServiceContract $settingsService,
    ) {}

    public function handle(OrderCreatedEvent $event): void
    {
        $order = $event->getOrder();

        try {
            $order->notify(new OrderCreated($order));
            $admins = User::query()->whereIn('email', $this->settingsService->getAdminMails())->get();
            Notification::send($admins, new OrderCreated($order));
        } catch (Throwable) {
            Log::error("Couldn't send order confirmation to the address: {$order->email}");
        }
    }
}
