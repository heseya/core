<?php

namespace App\Listeners;

use App\Events\ItemUpdatedQuantity;
use App\Services\Contracts\AvailabilityServiceContract;

class ItemUpdatedQuantityListener
{
    private AvailabilityServiceContract $availabilityService;

    public function __construct(AvailabilityServiceContract $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function handle(ItemUpdatedQuantity $event)
    {
        $this->availabilityService->calculateAvailabilityOnOrderAndRestock($event->getItem());
    }
}
