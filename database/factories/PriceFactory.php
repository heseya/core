<?php

namespace Database\Factories;

use App\Models\Model;
use App\Models\Price;
use Domain\Currency\Currency;
use Domain\Price\Enums\ProductPriceType;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Price::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'price_type' => ProductPriceType::PRICE_BASE->value,
            'value' => mt_rand(0, 1337),
            'currency' => Currency::getRandomValue(),
        ];
    }

    public function forModel(Model $model): static
    {
        return $this->state([
            'model_id' => $model->getKey(),
            'model_type' => $model::class,
        ]);
    }

    public function forAllCurrencies(): static
    {
        $sequences = Arr::map(Currency::values(), fn (string $currency) => ['currency' => $currency]);
        return $this->forEachSequence(...$sequences);
    }

    /**
     * @return Collection<int,array>
     */
    public function prepareForCreateMany(): Collection
    {
        return $this
            ->forAllCurrencies()
            ->make()
            ->map(fn (Price $price) => [
                'value' => $price->value,
                'price_type' => $price->price_type,
                'currency' => $price->currency,
            ]);
    }
}
