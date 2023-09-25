<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Dtos;

use App\Enums\ShippingType;
use App\Models\App;
use App\Models\User;
use App\Rules\Price;
use App\Rules\ShippingMethodPriceRanges;
use App\Traits\MapMetadata;
use Brick\Math\BigDecimal;
use Heseya\Dto\Missing;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Attributes\DataCollectionOf;
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

final class ShippingMethodUpdateDto extends Data
{
    use MapMetadata;

    /**
     * @param Optional|string $name
     * @param bool|Optional $public
     * @param bool|Optional $block_list
     * @param int|Optional $shipping_time_min
     * @param int|Optional $shipping_time_max
     * @param Optional|ShippingType $shipping_type
     * @param bool|Optional $payment_on_delivery
     * @param array<array<string>>|Optional $shipping_points
     * @param array<int>|Optional $payment_methods
     * @param string[]|Optional $sales_channels
     * @param array<string>|Optional $countries
     * @param DataCollection<int, PriceRangeDto>|Optional $price_ranges
     * @param array<string, string>|Missing|Optional $metadata
     * @param string|null $integration_key
     * @param string|null $app_id
     */
    public function __construct(
        #[StringType]
        public readonly Optional|string $name,

        #[BooleanType]
        public readonly bool|Optional $public,

        #[BooleanType]
        public readonly bool|Optional $block_list,

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
        public readonly array|Optional $sales_channels,

        #[ArrayType]
        public readonly array|Optional $countries,

        #[DataCollectionOf(PriceRangeDto::class)]
        public readonly DataCollection|Optional $price_ranges,

        public array|Missing|Optional $metadata = new Missing(),

        #[StringType]
        public readonly string|null $integration_key = null,

        #[StringType, Nullable]
        public string|null $app_id = null,
    ) {
        /** @var User|App|null $user */
        $user = Auth::user();
        $this->app_id = $user instanceof App ? $user->id : null;

        $this->metadata = self::mapMetadata(request());
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
            'sales_channels' => ['array'],
            'sales_channels.*' => ['string', 'exists:sales_channels,id'],
        ];
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
}
