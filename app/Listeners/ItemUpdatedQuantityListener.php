<?php

namespace App\Listeners;

use App\Events\ItemUpdatedQuantity;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\ItemServiceContract;

final readonly class ItemUpdatedQuantityListener
{
    public function __construct(
        private AvailabilityServiceContract $availabilityService,
        private ItemServiceContract $itemServiceContract,
    ) {}

    public function handle(ItemUpdatedQuantity $event): void
    {
        $this->availabilityService->calculateItemAvailability($event->getItem());

        // TODO: remove this with elastic refactor
        $this->itemServiceContract->refreshSearchable($event->getItem());
    }
}
