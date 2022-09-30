<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Schema;

class SchemaObserver
{
    public function deleting(Schema $schema): void
    {
        $schema->products->each(function (Product $product) use ($schema): void {
            if (!$product->schemas()->where('id', '!=', $schema->getKey())->exists()) {
                $product->update(['has_schemas' => false]);
            }
        });
    }
}
