<?php

namespace App\Observers;

use App\Models\Schema as DeprecatedSchema;
use Domain\ProductSchema\Models\Schema;

class SchemaObserver
{
    public function deleting(DeprecatedSchema|Schema $schema): void
    {
        if ($schema instanceof DeprecatedSchema) {
            foreach ($schema->products as $product) {
                if (!$product->schemas()->where('id', '!=', $schema->getKey())->exists()) {
                    $product->update(['has_schemas' => false]);
                }
            }
        } else {
            if ($schema->product && !$schema->product->schemas()->where('id', '!=', $schema->getKey())->exists()) {
                $schema->product->update(['has_schemas' => false]);
            }
        }
    }
}
