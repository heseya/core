<?php

namespace App\Listeners;

use App\Events\ItemUpdatedQuantity;
use App\Services\Contracts\AvailabilityServiceContract;

final readonly class ItemUpdatedQuantityListener
{
    public function __construct(
        private AvailabilityServiceContract $availabilityService,
    ) {}

    public function handle(ItemUpdatedQuantity $event): void
    {
        $this->availabilityService->calculateItemAvailability($event->getItem());
    }
}
