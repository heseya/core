<?php

declare(strict_types=1);

namespace Domain\ProductSchema\Dtos;

use App\Rules\Price;
use App\Rules\PricesEveryCurrency;
use Brick\Math\BigDecimal;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class SchemaUpdateDto extends SchemaDto
{
    /**
     * @return array<string,array<int,mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'prices' => ['sometimes', new PricesEveryCurrency()],
            'prices.*' => [new Price(['value'], min: BigDecimal::zero())],
        ];
    }
}
