<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Schema;

class SchemaObserver
{
    public function deleting(Schema $schema): void
    {
        $schema->products->each(function (Product $product) use ($schema): void {
            $productSchemas = $product->schemas()->where('id', '!=', $schema->id)->get();
            if ($productSchemas->isEmpty()) {
                $product->update(['has_schemas' => false]);
            }
        });
    }
}
