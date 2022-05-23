<?php

namespace App\Services;

use App\Models\Item;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\ShippingTimeDateServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ShippingTimeDateServiceService implements ShippingTimeDateServiceContract
{
    public function __construct(
        private AvailabilityServiceContract $availabilityService
    ) {
    }

    public function stopShippingUnlimitedStockDate(): void
    {
        $items = $this->getItemsWithUnlimitedStockDateLast24h();
        $items->each(fn ($item) => $this->availabilityService->calculateAvailabilityOnOrderAndRestock($item));
    }

    public function getItemsWithUnlimitedStockDateLast24h(): Collection
    {
        $between = [Carbon::now()->addDays(-1)->toDateTimeString(), Carbon::now()->toDateTimeString()];

        return Item::query()
            ->whereBetween('unlimited_stock_shipping_date', $between)
            ->get();
    }
}
