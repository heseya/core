<?php

namespace App\Services\Contracts;

use App\Dtos\ProductSetDto;
use App\Dtos\ProductSetUpdateDto;
use App\Dtos\ProductsReorderDto;
use App\Models\ProductSet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductSetServiceContract
{
    public function authorize(ProductSet $set): void;

    public function searchAll(array $attributes, bool $root): LengthAwarePaginator;

    public function create(ProductSetDto $dto): ProductSet;

    public function update(ProductSet $set, ProductSetUpdateDto $dto): ProductSet;

    public function reorder(ProductSet $parent, array $sets): void;

    public function updateChildren(
        Collection $children,
        string $parentId,
        string $parentSlug,
        bool $publicParent
    ): void;

    public function delete(ProductSet $set): void;

    public function products(ProductSet $set): LengthAwarePaginator;

    public function flattenSetsTree(Collection $sets, string $relation): Collection;

    public function flattenParentsSetsTree(Collection $sets): Collection;

    public function attach(ProductSet $set, array $products): Collection;

    public function reorderProducts(ProductSet $set, ProductsReorderDto $dto): void;
}
