<?php

namespace App\Events;

use Domain\Currency\Currency;
use Illuminate\Support\Arr;
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
        $prices = [];
        foreach (Currency::cases() as $currency) {
            $prices[] = [
                'currency' => $currency->value,
                'old_price_min' => $this->oldPricesMin
                    ? Arr::first($this->oldPricesMin, fn ($price) => $price['currency'] === $currency->value)['value']->getAmount() : null,
                'old_price_max' => $this->oldPricesMax
                    ? Arr::first($this->oldPricesMax, fn ($price) => $price['currency'] === $currency->value)['value']->getAmount() : null,
                'new_price_min' => Arr::first($this->newPricesMin, fn ($price) => $price['currency'] === $currency->value)['value']->getAmount(),
                'new_price_max' => Arr::first($this->newPricesMax, fn ($price) => $price['currency'] === $currency->value)['value']->getAmount(),
            ];
        }

        return [
            'id' => $this->id,
            'prices' => $prices,
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
