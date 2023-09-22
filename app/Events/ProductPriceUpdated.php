<?php

namespace App\Events;

use App\Http\Resources\PriceResource;

class ProductPriceUpdated extends WebHookEvent
{
    public function __construct(
        private readonly string $id,
        private readonly ?array $oldPricesMin,
        private readonly ?array $oldPricesMax,
        private readonly array $newPricesMin,
        private readonly array $newPricesMax,
    ) {
        parent::__construct();
    }

    public function getDataContent(): array
    {
        return [
            'id' => $this->id,
            'prices_min_old' => $this->oldPricesMin ? PriceResource::collection($this->oldPricesMin) : [],
            'prices_max_old' => $this->oldPricesMin ? PriceResource::collection($this->oldPricesMax) : [],
            'prices_min_new' => PriceResource::collection($this->newPricesMin),
            'prices_max_new' => PriceResource::collection($this->newPricesMax),
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
