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
     * @param PriceDto[] $prices
     */
    public function setSchemaPrices(Schema|string $schema, array $prices): void
    {
        if (is_string($schema)) {
            $schema = new ModelIdentityDto($schema, 'Schema');
        }

        app(PriceRepository::class)->setModelPrices($schema, ['schema' => $prices]);
    }
}
