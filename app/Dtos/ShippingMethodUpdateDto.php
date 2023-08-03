<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Models\App;
use App\Models\User;
use App\Traits\MapMetadata;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ShippingMethodUpdateDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public function __construct(
        public readonly Missing|string $name = new Missing(),
        public readonly bool|Missing $public = new Missing(),
        public readonly bool|Missing $block_list = new Missing(),
        public readonly ?array $payment_methods = null,
        public readonly ?array $countries = null,
        /** @var PriceRangeDto[]|null $price_ranges */
        public readonly ?array $price_ranges = null,
        public readonly int|Missing $shipping_time_min = new Missing(),
        public readonly int|Missing $shipping_time_max = new Missing(),
        public readonly Missing|string $shipping_type = new Missing(),
        public readonly string|null $integration_key = null,
        public readonly ?array $shipping_points = null,
        public readonly string|null $app_id = null,

        public readonly array|Missing $metadata = new Missing(),
    ) {}

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public static function instantiateFromRequest(
        FormRequest|ShippingMethodUpdateRequest $request,
    ): self {
        /** @var User|App|null $user */
        $user = Auth::user();

        $price_ranges = $request->input('price_ranges') ? array_map(
            fn ($data) => PriceRangeDto::fromData(...$data),
            $request->input('price_ranges'),
        ) : null;

        return new self(
            name: $request->input('name', new Missing()),
            public: $request->input('public', new Missing()),
            block_list: $request->input('block_list', new Missing()),
            payment_methods: $request->input('payment_methods'),
            countries: $request->input('countries'),
            price_ranges: $price_ranges,
            shipping_time_min: $request->input('shipping_time_min', new Missing()),
            shipping_time_max: $request->input('shipping_time_max', new Missing()),
            shipping_type: $request->input('shipping_type', new Missing()),
            integration_key: $request->input('integration_key'),
            shipping_points: $request->input('shipping_points'),
            app_id: $user instanceof App ? $user->id : null,
            metadata: self::mapMetadata($request),
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function isPublic(): bool|Missing
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
     * @return PriceRangeDto[]|null
     */
    public function getPriceRanges(): ?array
    {
        return $this->price_ranges;
    }

    public function getShippingPoints(): ?array
    {
        return $this->shipping_points;
    }

    public function getAppId(): string|null
    {
        return $this->app_id;
    }
}
