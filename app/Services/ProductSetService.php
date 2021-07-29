<?php

namespace App\Services;

use App\Dtos\ProductSetDto;
use App\Exceptions\StoreException;
use App\Models\ProductSet;
use App\Services\Contracts\ProductSetServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public function brands(array $attributes)
    {
        $query = ProductSet::whereHas(
            'parent',
            fn (Builder $sub) => $sub->where('slug', 'brands'),
        )->search($attributes);

        if (!Auth::check()) {
            $query->public();
        }

        return $query->get();
    }

    public function categories(array $attributes)
    {
        $query = ProductSet::whereHas(
            'parent',
            fn (Builder $sub) => $sub->where('slug', 'categories'),
        )->search($attributes);

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
            $slug = $dto->isSlugOverridden() ? $dto->getSlugSuffix() : $parent->slug . '-' . $dto->getSlugSuffix();
            $publicParent = $parent->public && $parent->public_parent;
        } else {
            $last = ProductSet::reversed()->first();

            $order = $last ? $last->order + 1 : 0;
            $slug = $dto->getSlugSuffix();
            $publicParent = true;
        }

        Validator::make(['slug' => $slug], [
            'slug' => 'unique:product_sets,slug',
        ])->validate();

        $set = ProductSet::create($dto->toArray() + [
            'order' => $order,
            'slug' => $slug,
            'public_parent' => $publicParent,
        ]);

        $publicParent = $publicParent && $dto->isPublic();

        if ($dto->getChildrenIds()->isNotEmpty()) {
            $dto->getChildrenIds()->each(
                function ($id, $order) use ($set, $slug, $publicParent) {
                    $child = ProductSet::findOrFail($id);

                    if ($child->slugOverride) {
                        $childSlug = $child->slug;
                    } else {
                        $childSlug = $slug . '-' . $child->slugSuffix;
                    }

                    $child->update([
                        'parent_id' => $set->getKey(),
                        'order' => $order,
                        'slug' => $childSlug,
                        'public_parent' => $publicParent,
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

            $publicParent = $parent->public && $parent->public_parent;
            $slug = $dto->isSlugOverridden() ? $dto->getSlugSuffix() :
                $parent->slug . '-' . $dto->getSlugSuffix();
        } else {
            $last = ProductSet::reversed()->first();

            if ($parentId !== $dto->getParentId()) {
                $order = $last ? $last->order + 1 : 0;
            } else {
                $order = $set->order;
            }

            $publicParent = true;
            $slug = $dto->getSlugSuffix();
        }

        Validator::make(['slug' => $slug], [
            'slug' => Rule::unique('product_sets', 'slug')->ignoreModel($set),
        ])->validate();

        $set->update($dto->toArray() + [
            'order' => $order,
            'slug' => $slug,
            'public_parent' => $publicParent,
        ]);

        $publicParent = $publicParent && $dto->isPublic();

        $rootOrder = ProductSet::reversed()->first()->order + 1;

        $set->children->each(fn ($child, $order) => $child->update([
            'parent_id' => null,
            'order' => $rootOrder + $order,
        ]));

        $dto->getChildrenIds()->each(
            function ($id, $order) use ($set, $slug, $publicParent) {
                $child = ProductSet::findOrFail($id);

                if ($child->slugOverride) {
                    $childSlug = $child->slug;
                } else {
                    $childSlug = $slug . '-' . $child->slugSuffix;
                }

                $child->update([
                    'parent_id' => $set->getKey(),
                    'order' => $order,
                    'slug' => $childSlug,
                    'public_parent' => $publicParent,
                ]);
            },
        );

        return $set;
    }

    public function reorder(ProductSet $parent, array $sets)
    {
        foreach ($sets as $id) {
            ProductSet::where('parent_id', $parent ? $parent->getKey() : null)
                ->findOrFail($id);
        }

        foreach ($sets as $key => $id) {
            ProductSet::where('id', $id)->update(['order' => $key]);
        }
    }

    public function delete(ProductSet $set)
    {
        if ($set->products()->count() > 0 || $set->children()->count() > 0) {
            throw new StoreException(__('admin.error.delete_with_relations'));
        }

        $set->delete();
    }
}
