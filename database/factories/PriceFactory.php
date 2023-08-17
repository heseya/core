<?php

namespace Database\Factories;

use App\Enums\Product\ProductPriceType;
use App\Models\Model;
use App\Models\PaymentMethod;
use App\Models\Price;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        return $this->count(count(Currency::values()))
            ->sequence(
                fn (Sequence $sequence) => ['currency' => Currency::values()[$sequence->index]]
            );
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
