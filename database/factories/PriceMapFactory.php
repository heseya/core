<?php

namespace Database\Factories;

use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PriceMapFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PriceMap::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'currency' => Currency::getRandomValue(),
            'description' => $this->faker->sentence(),
            'is_net' => $this->faker->boolean(),
            'name' => $this->faker->word(),
        ];
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
            ->map(fn (PriceMap $priceMap) => [
                'currency' => $priceMap->currency,
                'description' => $priceMap->description,
                'is_net' => $priceMap->is_net,
                'name' => $priceMap->name,
            ]);
    }
}
