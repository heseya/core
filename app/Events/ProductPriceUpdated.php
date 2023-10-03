<?php

namespace App\Events;

use App\Http\Resources\PriceResource;
use Illuminate\Support\Carbon;

class ProductPriceUpdated extends WebHookEvent
{
    private string $updatedAt;

    public function __construct(
        private readonly string $id,
        private readonly ?array $oldPricesMin,
        private readonly ?array $oldPricesMax,
        private readonly array $newPricesMin,
        private readonly array $newPricesMax,
    ) {
        $this->updatedAt = Carbon::now()->toIso8601String();
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
