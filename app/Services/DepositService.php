<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\Item;
use App\Services\Contracts\DepositServiceContract;

class DepositService implements DepositServiceContract
{
    public function getDepositsGroupByDateForItem(Item $item, string $order = 'ASC'): array
    {
        return Deposit::query()->selectRaw('SUM(quantity) as quantity, shipping_date')
            ->whereNotNull('shipping_date')
            ->where('item_id', '=', $item->getKey())
            ->groupBy('shipping_date')
            ->orderBY('shipping_date', $order)
            ->get()->toArray();
    }

    public function getDepositsGroupByTimeForItem(Item $item, string $order = 'ASC'): array
    {
        return Deposit::query()->selectRaw('SUM(quantity) as quantity, shipping_time')
            ->whereNotNull('shipping_time')
            ->where('item_id', '=', $item->getKey())
            ->groupBy('shipping_time')
            ->orderBY('shipping_time', $order)
            ->get()->toArray();
    }
}
