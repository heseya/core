<?php

namespace App\Services\Contracts;

use App\Dtos\ProductSetDto;
use App\Models\ProductSet;
use Illuminate\Support\Collection;

interface ProductSetServiceContract
{
    public function authorize(ProductSet $set): void;

    public function searchAll(array $attributes): Collection;

    public function brands(array $attributes): Collection;

    public function categories(array $attributes): Collection;

    public function create(ProductSetDto $dto): ProductSet;

    public function update(ProductSet $set, ProductSetDto $dto): ProductSet;

    public function reorder(ProductSet $parent, array $sets): void;

    public function updateChildren(
        Collection $children,
        string $parentId,
        string $parentSlug,
        bool $publicParent
    ): void;

    public function delete(ProductSet $set): void;
}
