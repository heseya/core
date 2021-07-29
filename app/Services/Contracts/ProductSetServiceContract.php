<?php

namespace App\Services\Contracts;

use App\Dtos\ProductSetDto;
use App\Models\ProductSet;
use Illuminate\Database\Eloquent\Collection;

interface ProductSetServiceContract
{
    public function searchAll(array $attributes): Collection;

    public function create(ProductSetDto $dto): ProductSet;

    public function update(ProductSet $set, ProductSetDto $dto): ProductSet;

    public function reorder(ProductSet $parent, array $sets): void;

    public function delete(ProductSet $set): void;
}
