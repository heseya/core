<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\ReorderServiceContract;
use App\Services\Contracts\SchemaServiceContract;

class SchemaService implements SchemaServiceContract
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
    }
}
