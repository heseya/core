<?php

namespace App\Events;

use App\Http\Resources\PriceResource;
use Domain\Price\Dtos\PriceDto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProductPriceUpdated extends WebHookEvent
{
    private string $updatedAt;

    /**
     * @param Collection<int,PriceDto>|null $oldPricesMin
     * @param Collection<int,PriceDto>|null $oldPricesMax
     * @param Collection<int,PriceDto> $newPricesMin
     * @param Collection<int,PriceDto> $newPricesMax
     */
    public function __construct(
        private readonly string $id,
        private readonly ?Collection $oldPricesMin,
        private readonly ?Collection $oldPricesMax,
        private readonly Collection $newPricesMin,
        private readonly Collection $newPricesMax,
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
