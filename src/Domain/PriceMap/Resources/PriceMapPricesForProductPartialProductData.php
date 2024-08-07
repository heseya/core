<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use App\Models\Product;
use Domain\PriceMap\PriceMapProductPrice;
use Exception;
use Support\Dtos\DataWithGlobalMetadata;

final class PriceMapPricesForProductPartialProductData extends DataWithGlobalMetadata
{
    public function __construct(
        public string $product_id,
        public string $product_price,
        public string|null $product_name = null,
    ) {}

    public static function fromProduct(Product $product): static
    {
        if (!$product->relationLoaded('mapPrices')) {
            throw new Exception('Can only create this from Product if mapPrices relation was previously loaded');
        }

        return new self(
            $product->id,
            (string) ($product->mapPrices->first()?->value ?? '0'),
            $product->name,
        );
    }

    public static function fromModel(PriceMapProductPrice $price): static
    {
        return new self(
            $price->product_id,
            (string) $price->value,
            $price->product?->name,
        );
    }

    public function setProduct(Product $product): self
    {
        $this->product_name = $product->name;

        return $this;
    }
}
