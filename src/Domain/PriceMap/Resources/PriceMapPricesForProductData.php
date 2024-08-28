<?php

declare(strict_types=1);

namespace Domain\PriceMap\Resources;

use App\Models\Option;
use App\Models\Product;
use Domain\PriceMap\PriceMapSchemaOptionPrice;
use Domain\ProductSchema\Models\Schema;
use Exception;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\DataCollection;
use Support\Dtos\DataWithGlobalMetadata;
use Support\LaravelData\Transformers\WithoutWrappingTransformer;

final class PriceMapPricesForProductData extends DataWithGlobalMetadata
{
    /**
     * @param DataCollection<int,PriceMapPricesForProductPartialSchemaOptionData> $schema_options
     */
    public function __construct(
        public string $product_id,
        public string $product_price,
        public string|null $product_name = null,
        #[WithTransformer(WithoutWrappingTransformer::class)]
        #[DataCollectionOf(PriceMapPricesForProductPartialSchemaOptionData::class)]
        public DataCollection $schema_options,
    ) {}

    public static function fromProduct(Product $product): static
    {
        if (!$product->relationLoaded('mapPrices')) {
            throw new Exception('Can only create this from Product if mapPrices relation was previously loaded');
        }

        if (!$product->relationLoaded('schemas')) {
            throw new Exception('Can only create this from Product if schemas relation was previously loaded');
        }

        foreach ($product->schemas as $schema) {
            /** @var Schema $schema */
            if (!$schema->relationLoaded('options')) {
                throw new Exception('Can only create this from Product if schemas options relation was previously loaded');
            }
            foreach ($schema->options as $option) {
                /** @var Option $option */
                if (!$option->relationLoaded('mapPrices')) {
                    throw new Exception('Can only create this from Product if schemas options mapPrices relation was previously loaded');
                }
            }
        }

        $schema_options = [];
        /** @var Schema $schema */
        foreach ($product->schemas as $schema) {
            /** @var Option $option */
            foreach ($schema->options as $option) {
                /** @var PriceMapSchemaOptionPrice $price */
                foreach ($option->mapPrices as $price) {
                    $schema_options[] = [
                        'schema_id' => $schema->id,
                        'schema_name' => $schema->name,
                        'schema_option_id' => $option->id,
                        'schema_option_name' => $option->name,
                        'schema_option_price' => $price->value->getAmount(),
                    ];
                }
            }
        }

        return new self(
            $product->id,
            (string) ($product->mapPrices->first()?->value->getAmount() ?? '0'),
            $product->getTranslation('name', $product->getLocale(), $product->useFallbackLocale()),
            PriceMapPricesForProductPartialSchemaOptionData::collection($schema_options),
        );
    }
}
