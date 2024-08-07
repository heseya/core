<?php

namespace App\Events;

use Illuminate\Support\Carbon;

class ProductPriceUpdated extends WebHookEvent
{
    private string $updatedAt;

    public function __construct(
        private readonly string $id,
        private readonly ?float $oldPriceMin,
        private readonly ?float $oldPriceMax,
        private readonly float $newPriceMin,
        private readonly float $newPriceMax,
    ) {
        $this->updatedAt = Carbon::now()->toIso8601String();
        parent::__construct();
    }

    public function getDataContent(): array
    {
        return [
            'id' => $this->id,
            'old_price_min' => $this->oldPriceMin,
            'old_price_max' => $this->oldPriceMax,
            'new_price_min' => $this->newPriceMin,
            'new_price_max' => $this->newPriceMax,
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
