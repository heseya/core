<?php

namespace App\Services\Contracts;

use App\Models\ProductSet;

interface ProductSetServiceContract
{
    public function searchAll(array $attributes);

    public function create(array $attributes): ProductSet;

    public function update(ProductSet $set, array $attributes): ProductSet;

    public function reorder(array $sets);

    public function delete(ProductSet $set);
}
