<?php

declare(strict_types=1);

namespace Domain\ProductSchema;

use App\Enums\RelationAlias;
use App\Models\Option;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\PriceRepository;
use Support\Dtos\ModelIdentityDto;

final class OptionRepository
{
    /**
     * @param PriceDto[] $prices
     */
    public function setOptionPrices(Option|string $option, array $prices): void
    {
        if (is_string($option)) {
            $option = new ModelIdentityDto($option, RelationAlias::OPTION->value);
        }

        app(PriceRepository::class)->setModelPrices($option, ['option' => $prices]);
    }
}
