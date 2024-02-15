<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Dtos;

use App\Enums\ShippingType;
use App\Models\App;
use App\Models\User;
use App\Rules\Price;
use App\Rules\ShippingMethodPriceRanges;
use Brick\Math\BigDecimal;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Support\Utils\Map;

final class ShippingMethodUpdateDto extends Data
{
    /**
     * @var Optional|MetadataUpdateDto[]
     */
    #[Computed]
    #[MapOutputName('metadata')]
    public readonly array|Optional $metadata_computed;

    /**
     * @param Optional|string $name
     * @param bool|Optional $public
     * @param bool|Optional $is_block_list_countries
     * @param bool|Optional $is_block_list_products
     * @param int|Optional $shipping_time_min
     * @param int|Optional $shipping_time_max
     * @param Optional|ShippingType $shipping_type
     * @param bool|Optional $payment_on_delivery
     * @param array<array<string>>|Optional $shipping_points
     * @param array<int>|Optional $payment_methods
     * @param array<string>|Optional $countries
     * @param DataCollection<int, PriceRangeDto>|Optional $price_ranges
     * @param array<string>|Optional $product_ids
     * @param array<string>|Optional $product_set_ids
     * @param array<string, string>|Optional $metadata_public
     * @param array<string, string>|Optional $metadata_private
     * @param string|null $integration_key
     * @param string|null $app_id
     */
    public function __construct(
        #[StringType]
        public readonly Optional|string $name,

        #[BooleanType]
        public readonly bool|Optional $public,

        #[BooleanType]
        public readonly bool|Optional $is_block_list_countries,

        #[BooleanType]
        public readonly bool|Optional $is_block_list_products,

        #[IntegerType, Min(0)]
        public readonly int|Optional $shipping_time_min,

        #[IntegerType, Min(0), GreaterThanOrEqualTo('shipping_time_min')]
        public readonly int|Optional $shipping_time_max,

        #[WithCast(EnumCast::class, ShippingType::class)]
        #[Enum(ShippingType::class)]
        public readonly Optional|ShippingType $shipping_type,

        #[BooleanType]
        public bool|Optional $payment_on_delivery,

        #[ArrayType]
        public readonly array|Optional $shipping_points,

        #[ArrayType]
        public readonly array|Optional $payment_methods,
        #[ArrayType]
        public readonly array|Optional $countries,
        #[DataCollectionOf(PriceRangeDto::class)]
        public readonly DataCollection|Optional $price_ranges,

        #[ArrayType]
        public readonly array|Optional $product_ids,

        #[ArrayType]
        public readonly array|Optional $product_set_ids,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,

        #[StringType]
        public readonly string|null $integration_key = null,

        #[StringType, Nullable]
        public string|null $app_id = null,
    ) {
        /** @var User|App|null $user */
        $user = Auth::user();
        $this->app_id = $user instanceof App ? $user->id : null;

        $this->metadata_computed = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }

    /**
     * @return array<string, array<int,mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
            'metadata' => ['array'],
            'metadata_private' => ['array'],
            'price_ranges' => [new ShippingMethodPriceRanges()],
            'price_ranges.*' => [new Price(['value', 'start'], min: BigDecimal::zero())],
            'payment_methods' => ['array', 'prohibited_if:payment_on_delivery,true'],
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'shipping_points.*.id' => ['string', 'exists:addresses,id'],
        ];
    }

    public function getName(): Optional|string
    {
        return $this->name;
    }

    public function isPublic(): bool|Optional
    {
        return $this->public;
    }

    /**
     * @return array<int>|Optional
     */
    public function getPaymentMethods(): array|Optional
    {
        return $this->payment_methods;
    }

    /**
     * @return array<string>|Optional
     */
    public function getCountries(): array|Optional
    {
        return $this->countries;
    }

    /**
     * @return DataCollection<int, PriceRangeDto>|Optional
     */
    public function getPriceRanges(): DataCollection|Optional
    {
        return $this->price_ranges;
    }

    /**
     * @return array<array<string, string>>|Optional
     */
    public function getShippingPoints(): array|Optional
    {
        return $this->shipping_points;
    }

    public function getAppId(): string|null
    {
        return $this->app_id;
    }
}
