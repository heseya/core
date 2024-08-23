<?php

namespace App\Events;

use Domain\Price\Dtos\ProductCachedPriceDto;
use Illuminate\Support\Carbon;

class ProductPriceUpdated extends WebHookEvent
{
    private string $updatedAt;

    /**
     * @param array<int,ProductCachedPriceDto> $oldPricesMin
     * @param array<int,ProductCachedPriceDto> $newPricesMin
     */
    public function __construct(
        private readonly string $id,
        private readonly array $oldPricesMin,
        private readonly array $newPricesMin,
    ) {
        $this->updatedAt = Carbon::now()->toIso8601String();
        parent::__construct();
    }

    public function getDataContent(): array
    {
        return [
            'id' => $this->id,
            'prices_min_old' => ProductCachedPriceDto::collection($this->oldPricesMin),
            'prices_max_old' => [],
            'prices_min_new' => ProductCachedPriceDto::collection($this->newPricesMin),
            'prices_max_new' => [],
            'updated_at' => $this->updatedAt,
        ];
    }

    public function getEvent(): string
    {
        return 'ProductPriceUpdated';
    }

    public function getDataType(): string
    {
        return 'ProductPrices';
    }
}
