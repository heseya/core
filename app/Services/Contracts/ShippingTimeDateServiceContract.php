<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface ShippingTimeDateServiceContract
{
    public function stopShippingUnlimitedStockDate(): void;

    public function getItemsWithUnlimitedStockDateLast24h(): Collection;
}
