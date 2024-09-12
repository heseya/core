<?php

namespace Database\Factories;

use App\Models\Option;
use Domain\PriceMap\PriceMapService;
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
            'default' => false,
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

            /** @var PriceMapService $priceMapService */
            $priceMapService = app(PriceMapService::class);

            $prices = FakeDto::generatePricesInAllCurrencies($prices);

            $result->each(fn(Option $option) => $priceMapService->updateOptionPricesForDefaultMaps($option, $prices));
        }

        return $result->count() > 1 ? $result : $result->first();
    }
}
