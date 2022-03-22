<?php

namespace App\Observers;

use App\Models\Deposit;
use App\Services\Contracts\AvailabilityServiceContract;

class DepositObserver
{
    public function created(Deposit $deposit)
    {
        $deposit->item->increment('quantity', $deposit->quantity);
    }

}
