<?php

namespace App\Observers;

use App\Models\Deposit;

class DepositObserver
{
    public function created(Deposit $deposit)
    {
        $deposit->item->update([
            'quantity' => $deposit->item->quantity + $deposit->quantity,
        ]);
    }
}
