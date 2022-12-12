<?php

namespace App\Listeners;

use App\Events\ItemUpdatedQuantity;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\ItemServiceContract;

class ItemUpdatedQuantityListener
{
    public function __construct(
        private AvailabilityServiceContract $availabilityService,
        private ItemServiceContract $itemServiceContract,
    ) {
    }

    public function handle(ItemUpdatedQuantity $event): void
    {
        $this->availabilityService->calculateItemAvailability($event->getItem());
        $this->itemServiceContract->refreshSerchable($event->getItem());
    }
}
