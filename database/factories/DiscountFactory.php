<?php

namespace Database\Factories;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    protected const DESCRIPTIONS = [
        'Black Week',
        'Holidays 2021',
        'Holidays Sale',
        'Cyber Monday 2020',
        'Black Friday 21',
        'Halloween Sale',
    ];

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Discount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $type = DiscountType::getRandomInstance();

        return [
            'name' => $this->faker->word,
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'description' => mt_rand(0, 5) ? null : $this->faker->randomElement(self::DESCRIPTIONS),
            'type' => $type,
            'value' => $type->is(DiscountType::PERCENTAGE) ? mt_rand(1, 18) * 5 : $this->faker->randomFloat(2, 5, 40),
            'priority' => $this->faker->randomDigit(),
            'target_type' => DiscountTargetType::getRandomInstance(),
            'target_is_allow_list' => $this->faker->boolean,
        ];
    }
}
