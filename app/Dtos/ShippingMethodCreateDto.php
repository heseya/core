<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\ShippingMethodStoreRequest;
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

class ShippingMethodCreateDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    protected string $name;
    protected bool $public;
    protected bool $block_list;
    protected ?array $payment_methods;
    protected ?array $countries;
    /** @var PriceRangeDto[] $price_ranges */
    protected array $price_ranges;
    protected int|Missing $shipping_time_min;
    protected int|Missing $shipping_time_max;
    protected string $shipping_type;
    protected string|null $integration_key;
    protected ?array $shipping_points;
    protected string|null $app_id;
    protected array|Missing $metadata;

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public static function instantiateFromRequest(
        FormRequest|ShippingMethodStoreRequest|ShippingMethodUpdateRequest $request,
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
            block_list: $request->input('block_list'),
            payment_methods: $request->input('payment_methods'),
            countries: $request->input('countries'),
            price_ranges: $price_ranges,
            shipping_time_min: $request->input('shipping_time_min', new Missing()),
            shipping_time_max: $request->input('shipping_time_max', new Missing()),
            shipping_type: $request->input('shipping_type'),
            shipping_points: $request->input('shipping_points'),
            integration_key: $request->input('integration_key'),
            app_id: $user instanceof App ? Auth::id() : null,
            metadata: self::mapMetadata($request),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function isBlockList(): bool
    {
        return $this->block_list;
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

    public function getShippingTimeMin(): int|Missing
    {
        return $this->shipping_time_min;
    }

    public function getShippingTimeMax(): int|Missing
    {
        return $this->shipping_time_max;
    }

    public function getShippingType(): string
    {
        return $this->shipping_type;
    }

    public function getShippingPoints(): ?array
    {
        return $this->shipping_points;
    }

    public function getIntegrationKey(): string|null
    {
        return $this->integration_key;
    }

    public function getAppId(): string|null
    {
        return $this->app_id;
    }
}
