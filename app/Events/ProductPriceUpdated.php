<?php

namespace App\Events;

use Illuminate\Support\Carbon;

class ProductPriceUpdated extends WebHookEvent
{
    private string $updatedAt;

    public function __construct(
        private string $id,
        private ?float $oldPriceMin,
        private ?float $oldPriceMax,
        private float $newPriceMin,
        private float $newPriceMax,
    )
    {
        $this->updatedAt = Carbon::now()->toIso8601String();
        parent::__construct();
    }

    public function isHidden(): bool
    {
        return false;
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

    public function getDataType(): string
    {
        return 'ProductPrices';
    }
}
