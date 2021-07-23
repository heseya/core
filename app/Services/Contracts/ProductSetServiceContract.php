<?php

namespace App\Services\Contracts;

use App\Dtos\ProductSetDto;
use App\Models\ProductSet;

interface ProductSetServiceContract
{
    public function searchAll(array $attributes);

    public function create(ProductSetDto $dto): ProductSet;

    public function update(ProductSet $set, ProductSetDto $dto): ProductSet;

    public function reorder(ProductSet $parent, array $sets);

    public function delete(ProductSet $set);
}
