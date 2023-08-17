<?php

declare(strict_types=1);

namespace Domain\ProductSchema;

use App\Models\Schema;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\PriceRepository;
use Support\Dtos\ModelIdentityDto;

final class SchemaRepository
{
    /**
     * @param PriceDto[][] $priceMatrix
     */
    public function setSchemaPrices(Schema|string $schema, array $priceMatrix): void
    {
        if (is_string($schema)) {
            $schema = new ModelIdentityDto($schema, Schema::class);
        }

        app(PriceRepository::class)->setModelPrices($schema, $priceMatrix);
    }
}
