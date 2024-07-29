<?php

declare(strict_types=1);

namespace Domain\ProductSchema\Services;

use App\Models\Product;
use Domain\ProductSchema\Models\Schema;

final readonly class SchemaService
{
    /**
     * @param array<string> $schemas
     */
    public function sync(Product $product, array $schemas = []): void
    {
        foreach ($schemas as $schema) {
            Schema::where('id', $schema)->update(['product_id' => $product->getKey()]);
        }

        if ($product->schemas->isEmpty() && $product->has_schemas) {
            $product->update(['has_schemas' => false]);
        }
        if ($product->schemas->isNotEmpty() && !$product->has_schemas) {
            $product->update(['has_schemas' => true]);
        }

        $product->oldSchemas()->each(static function (Schema $schema) use ($product): void {
            if (empty($schema->product_id) || $schema->product_id !== $product->getKey()) {
                $schema->product()
                    ->associate($product)
                    ->save();
            }
        });
    }
}
