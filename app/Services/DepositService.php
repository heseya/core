<?php

namespace App\Services;

use App\Events\ItemUpdatedQuantity;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\OrderProduct;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Support\Carbon;

final class DepositService implements DepositServiceContract
{
    public function removeItemsFromWarehouse(array $itemsToRemove, OrderProduct $orderProduct): bool
    {
        $return = true;

        foreach ($itemsToRemove as $item) {
            $return = $return && $this->removeItemFromWarehouse($item['item'], $item['quantity'], $orderProduct);
        }

        return $return;
    }

    public function getTimeAndDateForCartItems(array $cartItems): array
    {
        $maxProductItemsTimeDate = ['shipping_time' => null, 'shipping_date' => null];
        foreach ($cartItems as $cartItem) {
            /** @var Item $item */
            $item = $cartItem['item'];
            $timeDate = $this->getShippingTimeDateForQuantity($item, $cartItem['quantity']);
            // if missing item return time/date as null
            if ($timeDate['shipping_time'] === null && $timeDate['shipping_date'] === null) {
                return $timeDate;
            }
            $maxProductItemsTimeDate = $this->maxShippingTimeAndDate($timeDate, $maxProductItemsTimeDate);
        }

        return $maxProductItemsTimeDate;
    }

    /**
     * @return array{shipping_time: (int|null), shipping_date: (\Carbon\Carbon|null)}
     */
    public function getShippingTimeDateForQuantity(Item $item, float $quantity = 1): array
    {
        $groupedDepositsByTime = $this->getShippingTimeForQuantity($item, $quantity);
        if ($groupedDepositsByTime['shipping_time'] !== null) {
            return ['shipping_time' => $groupedDepositsByTime['shipping_time'], 'shipping_date' => null];
        }

        if ($item->unlimited_stock_shipping_time !== null) {
            return ['shipping_time' => $item->unlimited_stock_shipping_time, 'shipping_date' => null];
        }

        $groupedDepositsByDate = $this->getShippingDateForQuantity($item, $groupedDepositsByTime['quantity']);
        if ($groupedDepositsByDate['shipping_date'] !== null) {
            return ['shipping_time' => null, 'shipping_date' => $groupedDepositsByDate['shipping_date']];
        }

        if (
            $item->unlimited_stock_shipping_date !== null
            && !$item->unlimited_stock_shipping_date->isPast()
        ) {
            return ['shipping_time' => null, 'shipping_date' => $item->unlimited_stock_shipping_date];
        }

        return ['shipping_time' => null, 'shipping_date' => null];
    }

    public function getShippingTimeForQuantity(Item $item, float $quantity = 1): array
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

    public function getShippingDateForQuantity(Item $item, float $quantity = 1): array
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
        return Deposit::query()
            ->selectRaw('SUM(quantity) as quantity, shipping_date')
            ->whereNotNull('shipping_date')
            ->where('item_id', '=', $item->getKey())
            ->where('from_unlimited', '=', false)
            ->where('shipping_date', '>=', Carbon::now())
            ->groupBy(['shipping_date'])
            ->having('quantity', '>', '0')
            ->orderBy('shipping_date', $order)
            ->get()->toArray();
    }

    public function getDepositsGroupByTimeForItem(Item $item, string $order = 'ASC'): array
    {
        return Deposit::query()
            ->selectRaw('SUM(quantity) as quantity, shipping_time')
            ->whereNotNull('shipping_time')
            ->where('item_id', '=', $item->getKey())
            ->having('quantity', '>', '0')
            ->groupBy('shipping_time')
            ->orderBy('shipping_time', $order)
            ->get()->toArray();
    }

    public function getDepositWithoutShipping(Item $item): array
    {
        return Deposit::query()->selectRaw('SUM(quantity) as quantity')
            ->whereNull('shipping_time')
            ->whereNull('shipping_date')
            ->having('quantity', '>', '0')
            ->where('item_id', '=', $item->getKey())
            ->groupBy('shipping_time')
            ->get()->toArray();
    }

    private function maxShippingTimeAndDate(array $timeDate1, array $timeDate2): array
    {
        if ($timeDate1['shipping_date'] !== null) {
            $timeDate2['shipping_time'] = null;
            $timeDate2['shipping_date'] = $timeDate2['shipping_date'] !== null ?
                max($timeDate2['shipping_date'], $timeDate1['shipping_date']) : $timeDate1['shipping_date'];
        } elseif ($timeDate2['shipping_date'] !== null) {
            return $timeDate2;
        } elseif ($timeDate1['shipping_time'] !== null) {
            $timeDate2['shipping_time'] = $timeDate2['shipping_time'] !== null ?
                max($timeDate2['shipping_time'], $timeDate1['shipping_time']) : $timeDate1['shipping_time'];
            $timeDate2['shipping_date'] = null;
        }

        return $timeDate2;
    }

    private function removeItemFromWarehouse(Item $item, float $quantity, OrderProduct $orderProduct): bool
    {
        $groupedDepositsByTime = $this->getShippingTimeForQuantity($item, $quantity);
        if ($groupedDepositsByTime['shipping_time'] !== null) {
            return $this->removeFromShippingTimeAndDate(
                $orderProduct,
                $item,
                $quantity,
                ['shipping_time' => $groupedDepositsByTime['shipping_time'], 'shipping_date' => null],
                false,
            );
        }
        if ($item->unlimited_stock_shipping_time !== null) {
            return $this->removeFromWarehouse(
                $orderProduct,
                $item,
                $quantity,
                ['shipping_time' => $item->unlimited_stock_shipping_time, 'shipping_date' => null],
                true,
            );
        }
        $groupedDepositsByDate = $this->getShippingDateForQuantity($item, $groupedDepositsByTime['quantity']);
        if ($groupedDepositsByDate['shipping_date'] !== null) {
            return $this->removeFromShippingTimeAndDate(
                $orderProduct,
                $item,
                $quantity,
                ['shipping_time' => null, 'shipping_date' => $groupedDepositsByDate['shipping_date']],
                false,
            );
        }
        if (
            $item->unlimited_stock_shipping_date !== null
            && !$item->unlimited_stock_shipping_date->isPast()
        ) {
            return $this->removeFromWarehouse(
                $orderProduct,
                $item,
                $quantity,
                ['shipping_time' => null, 'shipping_date' => $item->unlimited_stock_shipping_date],
                true,
            );
        }
        $deposits = $this->getDepositWithoutShipping($item);
        if (isset($deposits[0]['quantity'])) {
            return $this->removeFromWarehouse(
                $orderProduct,
                $item,
                $quantity,
                ['shipping_time' => null, 'shipping_date' => null],
                false,
            );
        }

        return false;
    }

    private function removeFromWarehouse(
        OrderProduct $orderProduct,
        Item $item,
        float $quantity,
        array $shippingTimeAndDate,
        bool $fromUnlimited,
    ): bool {
        $orderProduct->deposits()->create([
            'item_id' => $item->getKey(),
            'quantity' => -1 * $quantity,
            'from_unlimited' => $fromUnlimited,
        ] + $shippingTimeAndDate);
        ItemUpdatedQuantity::dispatch($item);

        return true;
    }

    private function removeFromShippingTimeAndDate(
        OrderProduct $orderProduct,
        Item $item,
        float $quantity,
        array $shippingTimeAndDate,
        bool $fromUnlimited,
    ): bool {
        if ($shippingTimeAndDate['shipping_date'] !== null) {
            $shippingDate = Carbon::parse($shippingTimeAndDate['shipping_date']);

            $groupedDepositsByDate = $this->getDepositsGroupByDateForItem($item, 'DESC');
            foreach ($groupedDepositsByDate as $deposit) {
                $depositDate = Carbon::parse($deposit['shipping_date']);

                if (!$depositDate->isAfter($shippingDate) && $quantity > 0) {
                    $quantity -= $deposit['quantity'];
                    $this->removeFromWarehouse(
                        $orderProduct,
                        $item,
                        $quantity < 0 ? $deposit['quantity'] + $quantity : $deposit['quantity'],
                        ['shipping_time' => null, 'shipping_date' => $deposit['shipping_date']],
                        $fromUnlimited,
                    );
                }
            }
        }

        if ($quantity > 0) {
            $groupedDepositsByTime = $this->getDepositsGroupByTimeForItem($item, 'DESC');
            foreach ($groupedDepositsByTime as $deposit) {
                if (($deposit['shipping_time'] <= $shippingTimeAndDate['shipping_time']
                        || $shippingTimeAndDate['shipping_time'] === null) && $quantity > 0) {
                    $quantity -= $deposit['quantity'];
                    $this->removeFromWarehouse(
                        $orderProduct,
                        $item,
                        $quantity < 0 ? $deposit['quantity'] + $quantity : $deposit['quantity'],
                        ['shipping_time' => $deposit['shipping_time'], 'shipping_date' => null],
                        false,
                    );
                }
            }
        }

        if ($quantity > 0) {
            $deposit = $this->getDepositWithoutShipping($item);
            if (isset($deposit[0]['quantity'])) {
                $this->removeFromWarehouse(
                    $orderProduct,
                    $item,
                    $quantity,
                    ['shipping_time' => null, 'shipping_date' => null],
                    false,
                );
            }
            $quantity -= $deposit['quantity'];
        }

        return $quantity <= 0;
    }
}
