<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\SchemaServiceContract;

class SchemaService implements SchemaServiceContract
{
    public function sync(Product $product, array $schemas = []): void
    {
        $product->schemas()->sync($this->reorder($schemas));
    }

    private function reorder(array $schemas): array
    {
        $array = [];

        foreach ($schemas as $key => $id) {
            $array[$id]['order'] = $key;
        }

        return $array;
    }
}
