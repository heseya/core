<?php

declare(strict_types=1);

namespace Domain\PersonalPrice;

use Domain\Currency\Currency;
use Domain\Price\Resources\ProductCachedPriceData;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Support\Dtos\DataWithGlobalMetadata;

final class PersonalPricePartialDto extends DataWithGlobalMetadata
{
    public function __construct(
        public string $net,
        public string $gross,
        #[WithCast(EnumCast::class, Currency::class)]
        public Currency $currency,
    ) {}

    public static function fromProductCachedPriceData(ProductCachedPriceData $data): self
    {
        return self::from([
            'net' => $data->net->getAmount()->__toString(),
            'gross' => $data->gross->getAmount()->__toString(),
            'currency' => $data->currency,
        ]);
    }
}
