<?php

namespace App\Observers;

use App\Models\Deposit;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

class DepositObserver
{
    public function created(Deposit $deposit): void
    {
        $depositService = App::make(DepositServiceContract::class);

        $deposit->item->increment('quantity', $deposit->quantity);
        $deposit->item->update([
            'shipping_time' => $depositService->getShippingTimeForQuantity($deposit->item)['shipping_time'] ??
                $deposit->item->unlimited_stock_shipping_time,
            'shipping_date' => $depositService->getShippingDateForQuantity($deposit->item)['shipping_date'] ??
                ($deposit->item->unlimited_stock_shipping_date >= Carbon::now() ?
                    $deposit->item->unlimited_stock_shipping_date :
                    null),
        ]);
    }
}
