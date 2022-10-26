<?php

namespace App\Services\Contracts;

use App\Models\Item;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Support\Collection;

interface DepositServiceContract
{
    public function removeItemsFromWarehouse(array $itemsToRemove, OrderProduct $orderProduct): bool;

    public function getTimeAndDateForCartItems(array $cartItems): array;

    public function getProductShippingTimeDate(Product $product): array;

    public function getMinShippingTimeDateForOptions(Collection $options): array;

    public function getShippingTimeDateForQuantity(Item $item, float $quantity = 1): array;

    public function getShippingTimeForQuantity(Item $item, float $quantity = 1): array;

    public function getShippingDateForQuantity(Item $item, float $quantity = 1): array;

    public function getDepositsGroupByDateForItem(Item $item, string $order = 'ASC'): array;

    public function getDepositsGroupByTimeForItem(Item $item, string $order = 'ASC'): array;
}
