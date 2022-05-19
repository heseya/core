<?php

namespace App\Services;

use App\Enums\SchemaType;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DepositService implements DepositServiceContract
{
    public function getProductShippingTimeDate(Product $product): array
    {
        //get max shipping time/date form items
        $maxProductItemsTimeDate = ['shipping_time' => null, 'shipping_date' => null];
        foreach ($product->items as $item) {
            $timeDate = $this->getShippingTimeDateForQuantity($item, $item->pivot->required_quantity);
            //if missing item return time/date as null
            if (is_null($timeDate['shipping_time']) && is_null($timeDate['shipping_date'])) {
                return $timeDate;
            }
            $maxProductItemsTimeDate = $this->maxShippingTimeAndDate($timeDate, $maxProductItemsTimeDate);
        }
        //if product do not have required schema then return max shipping time/date form items
        $requiredSelectSchemas = $product->requiredSchemas->where('type.value', SchemaType::SELECT);
        if ($requiredSelectSchemas->isEmpty() && $product->items->isNotEmpty()) {
            return $maxProductItemsTimeDate;
        }
        //if product got required schema then get max shipping time/date
        $maxSchemaTimeDate = ['shipping_time' => null, 'shipping_date' => null];
        /** @var Schema $schema */
        foreach ($requiredSelectSchemas as $schema) {
            $timeDate = ['shipping_time' => $schema->shipping_time, 'shipping_date' => $schema->shipping_date];
            //if required schema is not available return time/date as null
            if (is_null($timeDate['shipping_time']) && is_null($timeDate['shipping_date'])) {
                return $timeDate;
            }
            $maxSchemaTimeDate = $this->maxShippingTimeAndDate($timeDate, $maxSchemaTimeDate);
        }
        //from product items shipping time/date and required schema shipping time/date return max shipping time/date
        return $this->maxShippingTimeAndDate($maxProductItemsTimeDate, $maxSchemaTimeDate);
    }

    public function getMinShippingTimeDateForOptions(Collection $options): array
    {
        $minTimeDate = ['shipping_time' => null, 'shipping_date' => null];
        foreach ($options as $option) {
            if (is_null($option->shipping_time) && is_null($option->shipping_date)) {
                return ['shipping_time' => null, 'shipping_date' => null];
            }
            $timeDate = ['shipping_time' => $option->shipping_time, 'shipping_date' => $option->shipping_date];
            $minTimeDate = $this->minShippingTimeAndDate($timeDate, $minTimeDate);
        }
        return $minTimeDate;
    }

    public function getMaxShippingTimeDateForItems(Collection $items, int $quantity = 1): array
    {
        $maxTimeDate = ['shipping_time' => null, 'shipping_date' => null];
        foreach ($items as $item) {
            $timeDate = $quantity > 1 ? $this->getShippingTimeDateForQuantity($item, $quantity) :
                ['shipping_time' => $item->shipping_time, 'shipping_date' => $item->shipping_date];
            if (is_null($timeDate['shipping_time']) && is_null($timeDate['shipping_date'])) {
                return $timeDate;
            }
            $maxTimeDate = $this->maxShippingTimeAndDate($timeDate, $maxTimeDate);
        }

        return $maxTimeDate;
    }

    public function getShippingTimeDateForQuantity(Item $item, int $quantity = 1): array
    {
        $groupedDepositsByTime = $this->getShippingTimeForQuantity($item, $quantity);
        if (!is_null($groupedDepositsByTime['shipping_time'])) {
            return ['shipping_time' => $groupedDepositsByTime['shipping_time'], 'shipping_date' => null];
        }
        if (!is_null($item->unlimited_stock_shipping_time)) {
            return ['shipping_time' => $item->unlimited_stock_shipping_time, 'shipping_date' => null];
        }
        $groupedDepositsByDate = $this->getShippingDateForQuantity($item, $groupedDepositsByTime['quantity']);
        if (!is_null($groupedDepositsByDate['shipping_date'])) {
            return ['shipping_time' => null, 'shipping_date' => $groupedDepositsByDate['shipping_date']];
        }
        if (
            !is_null($item->unlimited_stock_shipping_date) &&
            $item->unlimited_stock_shipping_date >= Carbon::now()
        ) {
            return ['shipping_time' => null, 'shipping_date' => $item->unlimited_stock_shipping_date];
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
            ->orderBy('shipping_date', $order)
            ->get()->toArray();
    }

    public function getDepositsGroupByTimeForItem(Item $item, string $order = 'ASC'): array
    {
        return Deposit::query()->selectRaw('SUM(quantity) as quantity, shipping_time')
            ->whereNotNull('shipping_time')
            ->where('item_id', '=', $item->getKey())
            ->groupBy('shipping_time')
            ->orderBy('shipping_time', $order)
            ->get()->toArray();
    }

    private function maxShippingTimeAndDate(array $timeDate1, array $timeDate2): array
    {
        if (!is_null($timeDate1['shipping_date'])) {
            $timeDate2['shipping_time'] = null;
            $timeDate2['shipping_date'] = !is_null($timeDate2['shipping_date']) ?
                max($timeDate2['shipping_date'], $timeDate1['shipping_date']) : $timeDate1['shipping_date'];
        } elseif (!is_null($timeDate1['shipping_time'])) {
            $timeDate2['shipping_time'] = !is_null($timeDate2['shipping_time']) ?
                max($timeDate2['shipping_time'], $timeDate1['shipping_time']) : $timeDate1['shipping_time'];
            $timeDate2['shipping_date'] = null;
        }

        return $timeDate2;
    }

    private function minShippingTimeAndDate(array $timeDate1, array $timeDate2): array
    {
        if (!is_null($timeDate1['shipping_time'])) {
            $timeDate2['shipping_time'] = !is_null($timeDate2['shipping_time']) ?
                min($timeDate2['shipping_time'], $timeDate1['shipping_time']) : $timeDate1['shipping_time'];
            $timeDate2['shipping_date'] = null;
        } elseif (!is_null($timeDate1['shipping_date'])) {
            $timeDate2['shipping_time'] = null;
            $timeDate2['shipping_date'] = !is_null($timeDate2['shipping_date']) ?
                min($timeDate2['shipping_date'], $timeDate1['shipping_date']) : $timeDate1['shipping_date'];
        }

        return $timeDate2;
    }
}
