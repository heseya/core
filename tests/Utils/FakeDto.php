<?php

namespace Tests\Utils;

use App\Dtos\PriceDto;
use App\Dtos\PriceRangeDto;
use App\Dtos\ProductCreateDto;
use App\Dtos\ShippingMethodCreateDto;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Faker\Generator;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

final readonly class FakeDto
{
    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws DtoException
     */
    public static function shippingMethodCreate(array $data = []): ShippingMethodCreateDto
    {
        $faker = App::make(Generator::class);

        $currency = Currency::DEFAULT->value;

        $priceRange = new PriceRangeDto(
            Money::zero($currency),
            Money::of(round(mt_rand(500, 2000) / 100, 2), $currency),
        );

        return new ShippingMethodCreateDto(...$data + [
            'name' => $faker->randomElement([
                'dpd',
                'inpostkurier',
            ]),
            'public' => $faker->boolean,
            'block_list' => $faker->boolean,
            'price_ranges' => [$priceRange],
        ]);
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public static function productCreateDto(array $data = []): ProductCreateDto
    {
        $faker = App::make(Generator::class);
        $name = $faker->sentence(mt_rand(1, 3));
        $description = $faker->sentence(10);

        $price = new PriceDto(
            Money::of(
                round(mt_rand(500, 6000), -2),
                Currency::DEFAULT->value,
            )
        );

        $langId = App::getLocale();

        return new ProductCreateDto(...$data + [
            'translations' => [
                $langId => [
                    'name' => $name,
                    'description_html' => "<p>{$description}</p>",
                    'description_short' => $description,
                ],
            ],
            'published' => [$langId],
            'slug' => Str::slug($name) . '-' . mt_rand(1, 99999),
            'public' => $faker->boolean,
            'shipping_digital' => false,
            'prices_base' => [$price],
        ]);
    }
}
