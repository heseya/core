<?php

namespace Database\Factories;

use App\Enums\Product\ProductPriceType;
use App\Models\Schema;
use Domain\Price\PriceRepository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Tests\Utils\FakeDto;

class SchemaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schema::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence(10),
            'hidden' => mt_rand(0, 10) === 0,
            'required' => $this->faker->boolean,
            'max' => null,
            'min' => null,
            'default' => null,
            'pattern' => null,
            'validation' => null,
            'published' => [App::getLocale()],
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

            collect($result)->each(fn (Schema $schema) => $priceRepository->setModelPrices($schema, [
                ProductPriceType::PRICE_BASE->value => $prices
            ]));
        }

        return $result->count() > 1 ? $result : $result->first();
    }
}
