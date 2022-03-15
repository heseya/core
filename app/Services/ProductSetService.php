<?php

namespace App\Services;

use App\Dtos\ProductSetDto;
use App\Events\ProductSetCreated;
use App\Events\ProductSetDeleted;
use App\Events\ProductSetUpdated;
use App\Models\ProductSet;
use App\Services\Contracts\ProductSetServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductSetService implements ProductSetServiceContract
{
    public function __construct(
        protected SeoMetadataServiceContract $seoMetadataService,
    ) {
    }

    public function authorize(ProductSet $set): void
    {
        if (
            !Auth::user()->can('product_sets.show_hidden') &&
            !ProductSet::public()->where('id', $set->getKey())->exists()
        ) {
            throw new NotFoundHttpException();
        }
    }

    public function searchAll(array $attributes, bool $root): Collection
    {
        $query = ProductSet::search($attributes);

        if (!Auth::user()->can('product_sets.show_hidden')) {
            $query->public();
        }

        if ($root) {
            $query->root();
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

        $attributes = Collection::make($dto->getAttributesIds());
        if ($attributes->isNotEmpty()) {
            $set->attributes()->sync($attributes);
        }

        $children = collect($dto->getChildrenIds());
        if ($children->isNotEmpty()) {
            $children = $children->map(fn ($id) => ProductSet::findOrFail($id));
            $this->updateChildren($children, $set->getKey(), $slug, $publicParent && $dto->isPublic());
        }

        $set->seo()->save($this->seoMetadataService->create($dto->getSeo()));

        ProductSetCreated::dispatch($set);

        return $set;
    }

    public function updateChildren(
        Collection $children,
        string $parentId,
        string $parentSlug,
        bool $publicParent
    ): void {
        $children->each(
            function ($child, $order) use ($parentId, $parentSlug, $publicParent) {
                if ($child->slugOverride) {
                    $childSlug = $child->slug;
                } else {
                    $childSlug = $parentSlug . '-' . $child->slugSuffix;
                }

                $this->updateChildren(
                    $child->children,
                    $child->getKey(),
                    $childSlug,
                    $publicParent && $child->public
                );

                $child->update([
                    'parent_id' => $parentId,
                    'order' => $order,
                    'slug' => $childSlug,
                    'public_parent' => $publicParent,
                ]);
            },
        );
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

        $children = collect($dto->getChildrenIds())->map(fn ($id) => ProductSet::findOrFail($id));
        $this->updateChildren($children, $set->getKey(), $slug, $publicParent && $dto->isPublic());

        $rootOrder = ProductSet::reversed()->first()->order + 1;

        $set->children()->whereNotIn('id', $dto->getChildrenIds())
            ->get()->each(fn ($child, $order) => $child->update([
                'parent_id' => null,
                'order' => $rootOrder + $order,
            ]));

        $set->update($dto->toArray() + [
            'order' => $order,
            'slug' => $slug,
            'public_parent' => $publicParent,
        ]);

        if ($dto->getAttributesIds() !== null) {
            $attributes = Collection::make($dto->getAttributesIds());
            $set->attributes()->sync($attributes);
        }

        $seo = $set->seo;
        if ($seo !== null) {
            $this->seoMetadataService->update($dto->getSeo(), $seo);
        }

        ProductSetUpdated::dispatch($set);

        return $set;
    }

    public function reorder(ProductSet $parent, array $sets): void
    {
        foreach ($sets as $id) {
            ProductSet::where('parent_id', $parent ? $parent->getKey() : null)
                ->findOrFail($id);
        }

        foreach ($sets as $key => $id) {
            ProductSet::where('id', $id)->update(['order' => $key]);
        }
    }

    public function attach(ProductSet $set, array $products): Collection
    {
        $set->products()->sync($products);

        return $set->products;
    }

    public function delete(ProductSet $set): void
    {
        if ($set->children()->count() > 0) {
            $set->children->each(fn ($subset) => $this->delete($subset));
        }

        $set->delete();

        if ($set->delete()) {
            ProductSetDeleted::dispatch($set);
            if ($set->seo !== null) {
                $this->seoMetadataService->delete($set->seo);
            }
        }
    }

    public function products(ProductSet $set): mixed
    {
        $query = $set->products();

        if (!Auth::user()->can('product_sets.show_hidden')) {
            $query->public();
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    public function flattenSetsTree(Collection $sets, string $relation): Collection
    {
        $subsets = $sets->map(
            fn ($set) => $this->flattenSetsTree($set->$relation, $relation),
        );

        return $subsets->flatten()->concat($sets);
    }
}
