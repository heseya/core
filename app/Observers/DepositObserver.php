<?php

namespace App\Observers;

use App\Models\Deposit;

class DepositObserver
{
    public function created(Deposit $deposit)
    {
        $deposit->item->increment('quantity', $deposit->quantity);
    }
}
