<?php

namespace Database\Factories;

use App\Enums\DiscountTargetType;
use App\Models\Discount;
use App\Repositories\DiscountRepository;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\App;

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
        return [
            'name' => $this->faker->word,
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'description' => mt_rand(0, 5) ? null : $this->faker->randomElement(self::DESCRIPTIONS),
            'percentage' => !!mt_rand(0, 1) ? mt_rand(1, 18) * 5 : null,
            'priority' => $this->faker->randomDigit(),
            'target_type' => DiscountTargetType::getRandomInstance(),
            'target_is_allow_list' => $this->faker->boolean,
            'published' => [App::getLocale()],
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Discount $discount): void {
            /** @var DiscountRepository $discountRepository */
            $discountRepository = App::make(DiscountRepository::class);

            if ($discount->percentage === null) {
                $amounts = array_map(fn (Currency $currency) => PriceDto::fromMoney(
                    Money::of(mt_rand(500, 2000) / 100.0, $currency->value),
                ), Currency::cases());

                $discountRepository->setDiscountAmounts($discount->getKey(), $amounts);
            }
        });
    }
}
