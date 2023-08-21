<?php

namespace Database\Factories;

use App\Enums\Product\ProductPriceType;
use App\Models\Option;
use Domain\Price\PriceRepository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Tests\Utils\FakeDto;

class OptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Option::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'disabled' => mt_rand(0, 10) === 0,
        ];
    }

    public function create($attributes = [], ?Model $parent = null)
    {
        $prices = $attributes['prices'] ?? [];
        unset($attributes['prices']);

        $result = parent::create($attributes, $parent);

        if (!empty($prices)) {
            if ($result instanceof Model) {
                $result = collect([$result]);
            }

            $priceRepository = app(PriceRepository::class);

            $prices = FakeDto::generatePricesInAllCurrencies($prices);

            $result->each(fn (Option $option) => $priceRepository->setModelPrices($option, [
                ProductPriceType::PRICE_BASE->value => $prices
            ]));
        }

        return $result->count() > 1 ? $result : $result->first();
    }
}
