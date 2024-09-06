<?php

declare(strict_types=1);

namespace Domain\Product\Resources;

use Domain\Price\Dtos\ProductCachedPriceDto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\LaravelData\Data;

final class ProductVariantPriceResource extends Data
{
    protected static string $_collectionClass = ProductVariantPriceResourceCollection::class;

    public function __construct(
        public string $product_id,
        public ProductCachedPriceDto $price,
        public ProductCachedPriceDto $price_initial,
    ) {}

    protected function calculateResponseStatus(Request $request): int
    {
        return Response::HTTP_OK;
    }
}
