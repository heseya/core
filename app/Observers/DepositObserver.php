<?php

namespace App\Observers;

use App\Models\Deposit;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Support\Facades\App;

final readonly class DepositObserver
{
    public function created(Deposit $deposit): void
    {
        if (!$deposit->item) {
            return;
        }

        /** @var DepositServiceContract $depositService */
        $depositService = App::make(DepositServiceContract::class);

        $deposit->item->update([
            'quantity' => $deposit->item->getQuantityRealAttribute(),
            ...$depositService->getShippingTimeDateForQuantity($deposit->item),
        ]);
    }
}
