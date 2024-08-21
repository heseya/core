<?php

declare(strict_types=1);

namespace Domain\PersonalPrice;

use App\Dtos\ProductPriceDto;
use App\Http\Resources\PriceResource;
use Support\Dtos\DataWithGlobalMetadata;

final class PersonalPriceDto extends DataWithGlobalMetadata
{
    public function __construct(
        public readonly string $id,
        public readonly PriceResource $price,
    ) {}

    public static function fromProductPriceDto(ProductPriceDto $dto)
    {
        return self::from([
            'id' => $dto->id,
            'price' => $dto->price_min,
        ]);
    }
}
