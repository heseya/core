<?php

namespace App\Services;

use App\Dtos\ProductSetDto;
use App\Exceptions\StoreException;
use App\Models\ProductSet;
use App\Services\Contracts\ProductSetServiceContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductSetService implements ProductSetServiceContract
{
    public function authorize(ProductSet $set)
    {
        if (
            !Auth::check() &&
            !ProductSet::public()->where('id', $set->getKey())->exists()
        ) {
            throw new NotFoundHttpException();
        }
    }

    public function searchAll(array $attributes)
    {
        $query = ProductSet::root()->search($attributes);

        if (!Auth::check()) {
            $query->public();
        }

        return $query->get();
    }

    public function create(ProductSetDto $dto): ProductSet
    {
        if ($dto->getParentId() !== null) {
            $parent = ProductSet::findOrFail($dto->getParentId());
            $lastChild = $parent->children()->reversed()->first();

            $order = $lastChild ? $lastChild->order + 1 : 0;
            $slug = $dto->getOverrideSlug() ? $dto->getOverrideSlug() : $parent->slug . '-' . $dto->getSlug();
        } else {
            $last = ProductSet::reversed()->first();
            
            $order = $last ? $last->order + 1 : 0;
            $slug = $dto->getOverrideSlug() ? $dto->getOverrideSlug() : $dto->getSlug();
        }

        $set = ProductSet::create($dto->toArray() + [
            'order' => $order,
            'slug' => $slug,
        ]);

        if ($dto->getChildrenIds()->isNotEmpty()) {
            $dto->getChildrenIds()->each(
                function ($id, $order) use ($set, $slug) {
                    $child = ProductSet::findOrFail($id);

                    if ($child->parent()->exists() &&
                        !Str::startsWith($child->slug, $child->parent->slug . '-')
                    ) {
                        $childSlug = $child->slug;
                    } else {
                        $childSlug = $slug . '-' . $child->slug;
                    }

                    $child->update([
                        'parent_id' => $set->getKey(),
                        'order' => $order,
                        'slug' => $childSlug,
                    ]);
                },
            );
        }

        return $set;
    }

    public function update(ProductSet $set, ProductSetDto $dto): ProductSet
    {
        $parentId = $set->parent ? $set->parent->getKey() : null;

        if ($dto->getParentId() !== null) {
            $parent = ProductSet::findOrFail($dto->getParentId());
            $lastChild = $parent->children()->reversed()->first();

            if ($parentId !== $dto->getParentId()) {
                $order = $lastChild ? $lastChild->order + 1 : 0;
            } else {
                $order = $set->order;
            }

            $slug = $dto->getOverrideSlug() ? $dto->getOverrideSlug() : $parent->slug . '-' . $dto->getSlug();
        } else {
            $last = ProductSet::reversed()->first();
            
            if ($parentId !== $dto->getParentId()) {
                $order = $last ? $last->order + 1 : 0;
            } else {
                $order = $set->order;
            }

            $slug = $dto->getOverrideSlug() ? $dto->getOverrideSlug() : $dto->getSlug();
        }

        $set->update($dto->toArray() + [
            'order' => $order,
            'slug' => $slug,
        ]);

        $dto->getChildrenIds()->each(
            function ($id, $order) use ($set, $slug) {
                $child = ProductSet::findOrFail($id);

                if ($child->parent()->exists() &&
                    !Str::startsWith($child->slug, $child->parent->slug . '-')
                ) {
                    $childSlug = $child->slug;
                } else {
                    $childSlug = $slug . '-' . $child->slug;
                }

                $child->update([
                    'parent_id' => $set->getKey(),
                    'order' => $order,
                    'slug' => $childSlug,
                ]);
            },
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
