<?php

declare(strict_types=1);

namespace Domain\Price\Resources;

use App\Models\Price;
use App\Models\Product;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\ProductCachedPriceDto;
use Exception;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Support\LaravelData\Casts\MoneyCast;

final class ProductCachedPriceData extends Data
{
    public function __construct(
        public string $product_id,
        #[WithCast(MoneyCast::class)]
        public Money $net,
        #[WithCast(MoneyCast::class)]
        public Money $gross,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
        #[Exists('sales_channels', 'id')]
        public string $sales_channel_id,
    ) {}

    public static function fromModel(Price $price): self
    {
        if ($price->model_type !== (new Product())->getMorphClass()) {
            throw new Exception();
        }

        return self::from([
            'product_id' => $price->model_id,
            'net' => $price->net,
            'gross' => $price->gross,
            'currency' => $price->currency,
            'sales_channel_id' => $price->sales_channel_id,
        ]);
    }

    public static function fromProductCachedPriceDto(string $product_id, ProductCachedPriceDto $dto): self
    {
        return self::from([
            'product_id' => $product_id,
            'net' => $dto->net,
            'gross' => $dto->gross,
            'currency' => $dto->currency,
            'sales_channel_id' => $dto->sales_channel_id,
        ]);
    }
}
