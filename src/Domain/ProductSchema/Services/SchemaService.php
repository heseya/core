<?php

declare(strict_types=1);

namespace Domain\ProductSchema\Services;

use App\Models\Product;
use App\Services\Contracts\ReorderServiceContract;
use Domain\ProductSchema\Models\Schema;

final readonly class SchemaService
{
    public function __construct(
        private ReorderServiceContract $reorderService,
    ) {}

    /**
     * @param array<string> $schemas
     */
    public function sync(Product $product, array $schemas = []): void
    {
        foreach ($schemas as $schema) {
            Schema::where('uuid', $schema)->update(['product_id' => $product->getKey()]);
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
