<?php

namespace App\Events;

use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Support\Carbon;

class ProductPriceUpdated extends WebHookEvent
{
    private string $updatedAt;

    public function __construct(
        private readonly string $id,
        private readonly ?Money $oldPriceMin,
        private readonly ?Money $oldPriceMax,
        private readonly Money $newPriceMin,
        private readonly Money $newPriceMax,
        private readonly Currency $currency = Currency::DEFAULT,
    ) {
        $this->updatedAt = Carbon::now()->toIso8601String();
        parent::__construct();
    }

    public function getDataContent(): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency->value,
            'old_price_min' => $this->oldPriceMin?->getAmount(),
            'old_price_max' => $this->oldPriceMax?->getAmount(),
            'new_price_min' => $this->newPriceMin->getAmount(),
            'new_price_max' => $this->newPriceMax->getAmount(),
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
