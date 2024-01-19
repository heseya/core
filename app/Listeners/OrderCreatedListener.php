<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCreated as OrderCreatedEvent;
use App\Mail\OrderCreated;
use Domain\Setting\Services\Contracts\SettingsServiceContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final readonly class OrderCreatedListener
{
    public function __construct(
        private SettingsServiceContract $settingsService,
    ) {}

    public function handle(OrderCreatedEvent $event): void
    {
        $order = $event->getOrder();

        try {
            Mail::to($order->email)
                ->locale($order->language)
                ->send(new OrderCreated($order));
        } catch (Throwable) {
            Log::error("Couldn't send order {$order->code} confirmation to the address: {$order->email}");
        }

        try {
            foreach ($this->settingsService->getAdminMails() as $admin) {
                Mail::to($admin)
                    ->locale('pl')
                    ->send(new OrderCreated($order));
            }
        } catch (Throwable) {
            Log::error("Couldn't send order {$order->code} confirmation to admins");
        }
    }
}
