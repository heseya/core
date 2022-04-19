<?php

namespace App\Observers;

use App\Models\Deposit;

class DepositObserver
{
    public function created(Deposit $deposit): void
    {
        $deposit->item->increment('quantity', $deposit->quantity);
    }
}
