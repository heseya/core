<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Enums\Currency;
use App\Http\Requests\ShippingMethodStoreRequest;
use App\Models\App;
use App\Models\User;
use App\Traits\MapMetadata;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Faker\Generator;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ShippingMethodCreateDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public function __construct(
        public readonly string $name,
        public readonly bool $public,
        public readonly bool $block_list,
        public readonly string $shipping_type,
        /** @var PriceRangeDto[] $price_ranges */
        public readonly array $price_ranges,
        public readonly ?array $payment_methods = null,
        public readonly ?array $countries = null,
        public readonly int|Missing $shipping_time_min = new Missing(),
        public readonly int|Missing $shipping_time_max = new Missing(),
        public readonly string|null $integration_key = null,
        public readonly ?array $shipping_points = null,
        public readonly string|null $app_id = null,
        public readonly array|Missing $metadata = new Missing(),
    ) {}

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public static function instantiateFromRequest(
        FormRequest|ShippingMethodStoreRequest $request,
    ): self {
        /** @var User|App|null $user */
        $user = Auth::user();

        $price_ranges = array_map(
            fn ($data) => PriceRangeDto::fromData(...$data),
            $request->input('price_ranges'),
        );

        return new self(
            name: $request->input('name'),
            public: $request->input('public'),
            block_list: $request->input('block_list', false),
            shipping_type: $request->input('shipping_type'),
            price_ranges: $price_ranges,
            payment_methods: $request->input('payment_methods'),
            countries: $request->input('countries'),
            shipping_time_min: $request->input('shipping_time_min', new Missing()),
            shipping_time_max: $request->input('shipping_time_max', new Missing()),
            integration_key: $request->input('integration_key'),
            shipping_points: $request->input('shipping_points'),
            app_id: $user instanceof App ? Auth::id() : null,
            metadata: self::mapMetadata($request),
        );
    }

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws DtoException
     */
    public static function fake(array $data = []): self
    {
        $faker = \Illuminate\Support\Facades\App::make(Generator::class);

        $currency = Currency::DEFAULT->value;

        $priceRange = new PriceRangeDto(
            Money::zero($currency),
            Money::of(round(mt_rand(500, 2000) / 100, 2), $currency),
        );

        return new self(...$data + [
            'name' => $faker->randomElement([
                'dpd',
                'inpostkurier',
            ]),
            'public' => $faker->boolean,
            'block_list' => $faker->boolean,
            'price_ranges' => [$priceRange],
        ]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function getPaymentMethods(): ?array
    {
        return $this->payment_methods;
    }

    public function getCountries(): ?array
    {
        return $this->countries;
    }

    /**
     * @return PriceRangeDto[]
     */
    public function getPriceRanges(): array
    {
        return $this->price_ranges;
    }

    public function getShippingPoints(): ?array
    {
        return $this->shipping_points;
    }
}
