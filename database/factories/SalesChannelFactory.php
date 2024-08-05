<?php

namespace Database\Factories;

use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Support\Str;
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
            'slug' => Str::limit($this->faker->unique()->slug(2), 30),
            'status' => $this->faker->randomElement(SalesChannelStatus::cases())->value,
            'language_id' => Language::default(),
            'default' => false,
            'activity' => $this->faker->randomElement(SalesChannelActivityType::cases())->value,

            // TODO: remove temp field
            'vat_rate' => '0',
        ];
    }
}
