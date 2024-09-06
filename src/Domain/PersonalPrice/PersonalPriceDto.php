<?php

declare(strict_types=1);

namespace Domain\PersonalPrice;

use Domain\Price\Dtos\ProductCachedPriceDto;
use Domain\Price\Resources\ProductCachedPriceData;
use Support\Dtos\DataWithGlobalMetadata;

final class PersonalPriceDto extends DataWithGlobalMetadata
{
    public function __construct(
        public readonly string $id,
        public readonly ProductCachedPriceDto $price,
    ) {}

    public static function fromProductCachedPriceData(ProductCachedPriceData $data): self
    {
        return self::from([
            'id' => $data->product_id,
            'price' => ProductCachedPriceDto::from($data),
        ]);
    }
}
