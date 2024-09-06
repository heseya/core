<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Enumerable;
use Spatie\LaravelData\DataCollection;

/**
 * @extends DataCollection<int,ProductVariantPriceDto>
 */
final class ProductVariantPriceDtoCollection extends DataCollection
{
    /**
     * @param DataCollection<int,ProductVariantPriceDto>|array<int,ProductVariantPriceDto>|array<int,array<string,mixed>> $items
     */
    public function __construct(
        string $dataClass = ProductVariantPriceDto::class,
        array|DataCollection|Enumerable|null $items = null,
    ) {
        $dataClass = ProductVariantPriceDto::class;
        parent::__construct($dataClass, $items);
    }

    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
