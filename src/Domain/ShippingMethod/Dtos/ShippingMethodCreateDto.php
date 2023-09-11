<?php

declare(strict_types=1);

namespace Domain\ShippingMethod\Dtos;

use App\Enums\ShippingType;
use App\Models\App;
use App\Models\User;
use App\Rules\Price;
use App\Rules\ShippingMethodPriceRanges;
use App\Traits\MapMetadata;
use App\Traits\MetadataRules;
use Brick\Math\BigDecimal;
use Heseya\Dto\Missing;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ShippingMethodCreateDto extends Data
{
    use MapMetadata;
    use MetadataRules;

    public readonly ?string $app_id;

    /**
     * @param string $name
     * @param bool $public
     * @param DataCollection<int, PriceRangeDto> $price_ranges
     * @param bool $payment_on_delivery
     * @param int $shipping_time_min
     * @param int $shipping_time_max
     * @param array<string, string>|Missing|Optional $metadata
     * @param array<int>|Optional $payment_methods
     * @param array<string>|Optional $countries
     * @param array<array<string>>|Optional $shipping_points
     * @param string|null $integration_key
     * @param bool $block_list
     */
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[BooleanType]
        public readonly bool $public,

        #[Enum(ShippingType::class)]
        #[WithCast(EnumCast::class, ShippingType::class)]
        public readonly ShippingType $shipping_type,

        #[DataCollectionOf(PriceRangeDto::class)]
        public readonly DataCollection $price_ranges,
        #[BooleanType]
        public readonly bool $payment_on_delivery,

        #[IntegerType, Min(0)]
        public readonly int $shipping_time_min,

        #[IntegerType, GreaterThanOrEqualTo('shipping_time_min')]
        public readonly int $shipping_time_max,

        #[ArrayType]
        public array|Missing|Optional $metadata,

        #[ArrayType]
        public readonly array|Optional $payment_methods,

        #[ArrayType]
        public readonly array|Optional $countries,

        #[ArrayType]
        public readonly array|Optional $shipping_points,

        #[StringType, Nullable]
        public readonly string|null $integration_key = null,

        #[BooleanType]
        public readonly bool $block_list = false,
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
            'price_ranges' => ['required', new ShippingMethodPriceRanges()],
            'price_ranges.*' => [new Price(['value', 'start'], min: BigDecimal::zero())],
            'payment_methods' => ['array', 'prohibited_if:payment_on_delivery,true'],
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'shipping_points.*.id' => ['string', 'exists:addresses,id'],
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPublic(): bool
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
     * @return array<int, string>|Optional
     */
    public function getCountries(): array|Optional
    {
        return $this->countries;
    }

    /**
     * @return DataCollection<int, PriceRangeDto>
     */
    public function getPriceRanges(): DataCollection
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
