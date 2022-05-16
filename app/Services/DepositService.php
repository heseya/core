<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\Item;
use App\Services\Contracts\DepositServiceContract;

class DepositService implements DepositServiceContract
{
    public function getShippingTimeDateForQuantity(Item $item, int $quantity): array
    {
        if ($this->getDepositsMaxQuantity($item) > $quantity) {
            $groupedDepositsByTime = $this->getShippingTimeForQuantity( $item, $quantity);
            if (!is_null($groupedDepositsByTime['shipping_time'])) {
                return ['shipping_time' =>  $groupedDepositsByTime['shipping_time'], 'shipping_date' => null];
            }
            if (!is_null($item->unlimited_stock_shipping_time)) {
                return ['shipping_time' =>  $item->unlimited_stock_shipping_time, 'shipping_date' => null];
            }
            $groupedDepositsByDate = $this->getShippingDateForQuantity( $item, $quantity);
            if (!is_null($groupedDepositsByDate['shipping_date'])) {
                return ['shipping_time' => null, 'shipping_date' => $groupedDepositsByDate['shipping_date']];
            }
            if (!is_null($item->unlimited_stock_shipping_date)) {
                return ['shipping_time' => null, 'shipping_date' => $item->unlimited_stock_shipping_date];
            }
        }

        return ['shipping_time' => null, 'shipping_date' => null];
    }

    public function getShippingTimeForQuantity(Item $item, int $quantity = 1): array
    {
        $groupedDepositsByTime = $this->getDepositsGroupByTimeForItem($item);
        foreach ($groupedDepositsByTime as $deposit) {
            if ($deposit['quantity'] >= $quantity) {
                return [
                    'quantity' => $quantity,
                    'shipping_time' => $deposit['shipping_time'],
                ];
            }
            $quantity -= $deposit['quantity'];
        }

        return [
            'quantity' => $quantity,
            'shipping_time' => null,
        ];
    }

    public function getShippingDateForQuantity(Item $item, int $quantity = 1): array
    {
        $groupedDepositsByDate = $this->getDepositsGroupByDateForItem($item);
        foreach ($groupedDepositsByDate as $deposit) {
            if ($deposit['quantity'] >= $quantity) {
                return [
                    'quantity' => $quantity,
                    'shipping_date' => $deposit['shipping_date'],
                ];
            }
            $quantity -= $deposit['quantity'];
        }

        return [
            'quantity' => $quantity,
            'shipping_date' => null,
        ];
    }

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

    public function getDepositsMaxQuantity(Item $item): float
    {
        return Deposit::query()->selectRaw('SUM(quantity) as quantity')
            ->where('item_id', '=', $item->getKey())
            ->first()->toArray()['quantity'] ?? 0;
    }
}
