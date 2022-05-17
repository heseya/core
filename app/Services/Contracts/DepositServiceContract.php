<?php

namespace App\Services\Contracts;

use App\Models\Item;
use Illuminate\Support\Collection;

interface DepositServiceContract
{
    public function getMinShippingTimeDateForOptions(Collection $options): array;

    public function getMaxShippingTimeDateForItems(Collection $items, int $quantity = 1): array;

    public function getShippingTimeDateForQuantity(Item $item, int $quantity = 1): array;

    public function getShippingTimeForQuantity(Item $item, int $quantity = 1): array;

    public function getShippingDateForQuantity(Item $item, int $quantity = 1): array;

    public function getDepositsGroupByDateForItem(Item $item, string $order = 'ASC'): array;

    public function getDepositsGroupByTimeForItem(Item $item, string $order = 'ASC'): array;
}
