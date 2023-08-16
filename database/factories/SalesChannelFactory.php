<?php

namespace Database\Factories;

use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Support\Enum\Status;

/**
 * @extends Factory<SalesChannel>
 */
class SalesChannelFactory extends Factory
{
    protected $model = SalesChannel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'slug' => $this->faker->unique()->slug(2),
            'status' => $this->faker->randomElement(Status::cases())->value,
            'countries_block_list' => false,
            'default_currency' => Currency::DEFAULT,
            'default_language_id' => Language::default(),

            // TODO: remove temp field
            'vat_rate' => '0',
        ];
    }
}
