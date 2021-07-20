<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\ReorderServiceContract;
use App\Services\Contracts\SchemaServiceContract;

class SchemaService implements SchemaServiceContract
{
    protected ReorderServiceContract $reorderService;

    public function __construct(ReorderServiceContract $reorderService)
    {
        $this->reorderService = $reorderService;
    }

    public function sync(Product $product, array $schemas = []): void
    {
        $product->schemas()->sync(
            $this->reorderService->reorder($schemas),
        );
    }
}
