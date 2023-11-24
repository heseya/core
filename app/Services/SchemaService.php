<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\ReorderServiceContract;
use App\Services\Contracts\SchemaServiceContract;

final readonly class SchemaService implements SchemaServiceContract
{
    public function __construct(
        private ReorderServiceContract $reorderService,
    ) {}

    public function sync(Product $product, array $schemas = []): void
    {
        $product->schemas()->sync(
            $this->reorderService->reorder($schemas),
        );

        if ($product->schemas->isEmpty() && $product->has_schemas) {
            $product->update(['has_schemas' => false]);
        }
        if ($product->schemas->isNotEmpty() && !$product->has_schemas) {
            $product->update(['has_schemas' => true]);
        }

        $product->schemas()->each(static function (Schema $schema) use ($product): void {
            if (empty($schema->product_id) || $schema->product_id !== $product->getKey()) {
                $schema
                    ->product()
                    ->associate($product)
                    ->save();
            }
        });
    }
}
