<?php

namespace App\Observers;

use App\Models\Deposit;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Support\Facades\App;

class DepositObserver
{
    public function created(Deposit $deposit): void
    {
        /** @var DepositServiceContract $depositService */
        $depositService = App::make(DepositServiceContract::class);

        if (!$deposit->from_unlimited) {
            $deposit->item?->increment('quantity', $deposit->quantity);
        }

        $deposit->item?->update($depositService->getShippingTimeDateForQuantity($deposit->item));
    }
}
