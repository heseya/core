<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Support\Facades\App;

class SchemaObserver
{
    public function deleted(Schema $schema): void
    {
        $productService = App::make(ProductServiceContract::class);
        $schema->products->each(fn (Product $product) => $productService->setProductHasSchemaAttribute($product));
    }
}
