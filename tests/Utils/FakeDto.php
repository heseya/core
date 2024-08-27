<?php

namespace Tests\Utils;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Product\Dtos\ProductCreateDto;
use Domain\ProductSchema\Dtos\SchemaDto;
use Domain\ProductSchema\Dtos\SchemaUpdateDto;
use Domain\ProductSchema\Models\Schema;
use Domain\ShippingMethod\Dtos\PriceRangeDto;
use Domain\ShippingMethod\Dtos\ShippingMethodCreateDto;
use Faker\Generator;
use Heseya\Dto\DtoException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;

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

        return ShippingMethodCreateDto::from(
            [
                ...$data + [
                    'name' => $faker->randomElement([
                        'dpd',
                        'inpostkurier',
                    ]),
                    'public' => $faker->boolean,
                    'block_list' => $faker->boolean,
                    'price_ranges' => [$priceRange],
                    'payment_on_delivery' => false,
                ],
            ]
        );
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public static function productCreateData(array $data = []): array
    {
        $keys = array_keys($data);

        return Arr::only(self::productCreateDto($data, true), $keys);
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public static function productCreateDto(array $data = [], bool $returnArray = false): ProductCreateDto|array
    {
        $faker = App::make(Generator::class);
        $name = $faker->sentence(mt_rand(1, 3));
        $description = $faker->sentence(10);

        $data['prices_base'] = self::generatePricesInAllCurrencies($data['prices_base'] ?? [])->toArray();

        $langId = App::getLocale();

        $data = $data + [
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
        ];

        if ($returnArray) {
            return $data;
        }

        return ProductCreateDto::from($data);
    }

    /**
     * @param PriceDto[]|DataCollection<int,PriceDto>|array<int,array<string,int|string>> $data
     *
     * @return DataCollection<PriceDto>
     */
    public static function generatePricesInAllCurrencies(
        array|DataCollection $data = [],
        BigDecimal|int|float|null $amount = null
    ): DataCollection {
        $prices = [];
        $usedCurrencies = [];

        foreach ($data as $price) {
            if (is_array($price) && Arr::has($price, ['value', 'currency'])) {
                $price = PriceDto::from($price);
            }
            if ($price instanceof PriceDto) {
                $amount = $amount ?? $price->value->getAmount();
                $usedCurrencies[] = $price->currency;
                $prices[] = $price;
            }
        }

        $amount = $amount ?? 0;

        foreach (Currency::cases() as $case) {
            if (!in_array($case, $usedCurrencies)) {
                $price = PriceDto::from([
                    'value' => $amount,
                    'currency' => $case->value,
                ]);
                $prices[] = $price;
            }
        }

        return PriceDto::collection($prices);
    }

    public static function schemaData(array $data = [], bool $addDefaultOption = true): array
    {
        $keys = array_keys($data);

        return Arr::only(self::schemaDto($data, true, $addDefaultOption), $keys);
    }

    public static function schemaDto(array $data = [], bool $returnArray = false, bool $addDefaultOption = true): SchemaDto|array
    {
        $data = $data + Schema::factory()->definition();
        unset($data['type']);

        $langId = App::getLocale();

        $data['translations'][$langId]['name'] = $data['translations'][$langId]['name'] ?? $data['name'];
        $data['translations'][$langId]['description'] = $data['translations'][$langId]['description'] ?? $data['description'];

        if ($addDefaultOption && (!array_key_exists('options', $data) || empty($data['options']))) {
            $data['options'] = [
                [
                    'name' => 'Test',
                    'prices' => self::generatePricesInAllCurrencies([PriceDto::from(Money::of(0, Currency::DEFAULT->toCurrencyInstance()))])->toArray(),
                ]
            ];
        }

        if (!empty($data['options'])) {
            foreach ($data['options'] as &$option) {
                $option['translations'][$langId]['name'] = $option['translations'][$langId]['name'] ?? $option['name'] ?? Str::random(
                    4
                );

                $option['prices'] = self::generatePricesInAllCurrencies($option['prices'] ?? [])->toArray();
            }

            if (($data['required'] ?? false) && empty($data['default'])) {
                $data['default'] = 0;
            }
        }

        if (($data['required'] ?? false) && empty($data['default']) && !empty($data['options'])) {
            $data['default'] = 0;
        }

        if ($returnArray) {
            return $data;
        }

        return SchemaUpdateDto::from($data);
    }
}
