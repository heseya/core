<?php

namespace App\Observers;

use App\Models\Deposit;
use App\Services\Contracts\AvailabilityServiceContract;

class DepositObserver
{
    private AvailabilityServiceContract $availabilityService;

    public function __construct(AvailabilityServiceContract $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function created(Deposit $deposit)
    {
        $deposit->item->increment('quantity', $deposit->quantity);
        $this->availabilityService->calculateAvailabilityOnOrderAndRestock($deposit->item);
    }

    public function deleted(Deposit $deposit)
    {
        $this->availabilityService->calculateAvailabilityOnOrderAndRestock($deposit->item);
    }
}
