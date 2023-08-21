<?php

declare(strict_types=1);

namespace Domain\ProductSchema;

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
            $option = new ModelIdentityDto($option, Option::class);
        }

        app(PriceRepository::class)->setModelPrices($option, ['option' => $prices]);
    }
}
