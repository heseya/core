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
        $query = ProductSet::root()->search($attributes);

        if (Auth::check()) {
            $query->private();
        }

        return $query->get();
    }

    public function create(ProductSetDto $dto): ProductSet
    {
        if ($dto->getParentId() !== null) {
            $parent = ProductSet::private()->findOrFail($dto->getParentId());
            $lastChild = $parent->children()->private()->reversed()->first();

            $order = $lastChild ? $lastChild->order + 1 : 0;
        } else {
            $last = ProductSet::private()->reversed()->first();
            
            $order = $last ? $last->order + 1 : 0;
        }

        $set = ProductSet::create($dto->toArray() + [
            'order' => $order,
        ]);

        if ($dto->getChildrenIds()->isNotEmpty()) {
            $dto->getChildrenIds()->each(
                fn ($id, $order) => ProductSet::private()->findOrFail($id)->update([
                    'parent_id' => $set->getKey(),
                    'order' => $order,
                ]),
            );
        }

        return $set;
    }

    public function update(ProductSet $set, ProductSetDto $dto): ProductSet
    {
        $parentId = $set->parent ? $set->parent->getKey() : null;

        if ($parentId !== $dto->getParentId()) {
            if ($dto->getParentId() !== null) {
                $parent = ProductSet::private()->findOrFail($dto->getParentId());
                $lastChild = $parent->children()->private()->reversed()->first();
    
                $order = $lastChild ? $lastChild->order + 1 : 0;
            } else {
                $last = ProductSet::private()->reversed()->first();
                
                $order = $last ? $last->order + 1 : 0;
            }
        } else {
            $order = $set->order;
        }

        $set->update($dto->toArray() + [
            'order' => $order,
        ]);

        // Safe detach
        $last = ProductSet::private()->reversed()->first(); 
        $childOrder = $last ? $last->order + 1 : 0;

        $set->children->each(
            fn ($id, $order) => ProductSet::private()->where('id', $id)->update([
                'parent_id' => $set->getKey(),
                'order' => $childOrder + $order,
            ]),
        );

        // Reattach
        $dto->getChildrenIds()->each(
            fn ($id, $order) => ProductSet::private()->findOrFail($id)->update([
                'parent_id' => $set->getKey(),
                'order' => $order,
            ]),
        );

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
