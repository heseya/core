<?php

namespace App\Services\Contracts;

use App\Dtos\CartDto;
use Illuminate\Support\Collection;

interface ShippingTimeDateServiceContract
{
    public function stopShippingUnlimitedStockDate(): void;

    public function getItemsWithUnlimitedStockDateLast24h(): Collection;

    public function getTimeAndDateForCart(CartDto $cart, Collection $products): array;
}
