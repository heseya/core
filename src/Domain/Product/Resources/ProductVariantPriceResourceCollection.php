<?php

declare(strict_types=1);

namespace Domain\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Enumerable;
use Spatie\LaravelData\DataCollection;

/**
 * @extends DataCollection<int,ProductVariantPriceResource>
 */
final class ProductVariantPriceResourceCollection extends DataCollection
{
    /**
     * @param DataCollection<int,ProductVariantPriceResource>|array<int,ProductVariantPriceResource>|array<int,array<string,mixed>> $items
     */
    public function __construct(
        string $dataClass = ProductVariantPriceResource::class,
        array|DataCollection|Enumerable|null $items = null,
    ) {
        $dataClass = ProductVariantPriceResource::class;
        parent::__construct($dataClass, $items);
    }

    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
