<?php

namespace App\Services;

use App\Dtos\ProductSetDto;
use App\Exceptions\StoreException;
use App\Models\ProductSet;
use App\Services\Contracts\ProductSetServiceContract;
use Illuminate\Support\Facades\Auth;

class ProductSetService implements ProductSetServiceContract
{
    public function searchAll(array $attributes)
    {
        $query = ProductSet::search($attributes)->orderBy('order');

        if (Auth::check()) {
            $query->private();
        }

        return $query->get();
    }

    public function create(ProductSetDto $dto): ProductSet
    {
        if ($dto->getParentId() !== null) {
            $parent = ProductSet::everything()->findOrFail($dto->getParentId());

            $order = $parent->children()->count();
        } else {
            $order = ProductSet::private()->count();
        }

        return ProductSet::create($dto->toArray() + [
            'order' => $order,
        ]);
    }

    public function update(ProductSet $set, ProductSetDto $dto): ProductSet
    {
        if ($set->parent->getKey() !== $dto->getParentId()) {
            if ($dto->getParentId() !== null) {
                $parent = ProductSet::everything()->findOrFail($dto->getParentId());

                $order = $parent->children()->count();
            } else {
                $order = ProductSet::private()->count();
            }
        } else {
            $order = $set->order;
        }

        $set->update($dto->toArray() + [
            'order' => $order,
        ]);

        return $set;
    }

    public function reorder(array $sets)
    {
        foreach ($sets as $key => $id) {
            ProductSet::where('id', $id)->update(['order' => $key]);
        }
    }

    public function delete(ProductSet $set)
    {
        if ($set->products()->count() > 0) {
            throw new StoreException(__('admin.error.delete_with_relations'));
        }

        $set->delete();
    }
}
