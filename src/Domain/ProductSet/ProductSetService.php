<?php

declare(strict_types=1);

namespace Domain\ProductSet;

use App\Dtos\ProductReorderDto;
use App\Dtos\ProductsReorderDto;
use App\Models\Product;
use App\Services\Contracts\MetadataServiceContract;
use App\Traits\GetPublishedLanguageFilter;
use Domain\ProductSet\Dtos\ProductSetCreateDto;
use Domain\ProductSet\Dtos\ProductSetUpdateDto;
use Domain\ProductSet\Events\ProductSetCreated;
use Domain\ProductSet\Events\ProductSetDeleted;
use Domain\ProductSet\Events\ProductSetUpdated;
use Domain\Seo\SeoMetadataService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ProductSetService
{
    use GetPublishedLanguageFilter;

    public function __construct(
        private SeoMetadataService $seoMetadataService,
        private MetadataServiceContract $metadataService,
    ) {}

    public function authorize(ProductSet $set): void
    {
        if (
            Gate::denies('product_sets.show_hidden')
            && !ProductSet::public()->where('id', $set->getKey())->exists()
        ) {
            throw new NotFoundHttpException();
        }
    }

    public function searchAll(array $attributes, bool $root): LengthAwarePaginator
    {
        $query = ProductSet::searchByCriteria($attributes + $this->getPublishedLanguageFilter('product_sets'))
            ->with(['metadata', 'media', 'media.metadata']);

        if (Gate::denies('product_sets.show_hidden')) {
            $query->with('childrenPublic')->public();
        } else {
            $query->with(['children', 'metadataPrivate', 'media.metadataPrivate']);
        }

        if ($root) {
            $query->root();
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @throws ValidationException
     */
    public function create(ProductSetCreateDto $dto): ProductSet
    {
        if ($dto->parent_id !== null) {
            /** @var ProductSet $parent */
            $parent = ProductSet::query()->findOrFail($dto->parent_id);
            $lastChild = $parent->children()->reversed()->first();

            $order = $lastChild ? $lastChild->order + 1 : 0;
            // Here slug is always string because slug_suffix is required when creating product set
            /** @var string $slug */
            $slug = $this->prepareSlug($dto->slug_override, $dto->slug_suffix, $parent->slug);
            $publicParent = $parent->public && $parent->public_parent;
        } else {
            $last = ProductSet::reversed()->first();

            $order = $last ? $last->order + 1 : 0;
            /** @var string $slug */
            $slug = $dto->slug_suffix;
            $publicParent = true;
        }

        Validator::make(['slug' => $slug], [
            'slug' => 'unique:product_sets,slug',
        ])->validate();

        $set = new ProductSet(
            $dto->toArray() + [
                'order' => $order,
                'slug' => $slug,
                'public_parent' => $publicParent,
            ],
        );

        foreach ($dto->translations as $lang => $translations) {
            $set->setLocale($lang)->fill($translations);
        }

        $set->save();
        $set->attributes()->sync($dto->attributes);
        $this->reorderAttributes($set, $dto->attributes);

        $children = Collection::make($dto->children_ids);
        if ($children->isNotEmpty()) {
            $children = $children->map(fn ($id) => ProductSet::query()->findOrFail($id));
            $this->updateChildren($children, $set->getKey(), $slug, $publicParent && $dto->public);
        }

        if (!($dto->seo instanceof Optional)) {
            $this->seoMetadataService->createOrUpdateFor($set, $dto->seo);
        }

        if (!($dto->metadata_computed instanceof Optional)) {
            $this->metadataService->sync($set, $dto->metadata_computed);
        }

        $set->refresh();

        // searchable is handled by the event listener
        ProductSetCreated::dispatch($set);

        return $set;
    }

    public function updateChildren(
        Collection $children,
        string $parentId,
        ?string $parentSlug,
        bool $publicParent,
    ): void {
        $children->each(
            function ($child, $order) use ($parentId, $parentSlug, $publicParent): void {
                if ($child->slugOverride) {
                    $childSlug = $child->slug;
                } else {
                    $childSlug = ($parentSlug ? $parentSlug . '-' : '') . $child->slugSuffix;
                }

                $this->updateChildren(
                    $child->children,
                    $child->getKey(),
                    $childSlug,
                    $publicParent && $child->public,
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

    public function update(ProductSet $set, ProductSetUpdateDto $dto): ProductSet
    {
        if (!($dto->parent_id instanceof Optional) && $dto->parent_id !== null) {
            /** @var ProductSet $parent */
            $parent = ProductSet::query()->findOrFail($dto->parent_id);

            if ($set->parent_id !== $dto->parent_id) {
                $lastChild = $parent->children()->reversed()->first();
                $order = $lastChild ? $lastChild->order + 1 : 0;
            } else {
                $order = $set->order;
            }

            $publicParent = $parent->public && $parent->public_parent;
            $override = $dto->slug_override instanceof Optional ? $set->slug_override : $dto->slug_override;
            $suffix = $dto->slug_suffix instanceof Optional ? $set->slug_suffix : $dto->slug_suffix;
            $slug = $this->prepareSlug($override, $suffix, $parent->slug);
        } else {
            if ($set->parent_id !== null) {
                $last = ProductSet::reversed()->first();
                $order = $last ? $last->order + 1 : 0;
            } else {
                $order = $set->order;
            }

            $publicParent = true;
            $slug = $dto->slug_suffix instanceof Optional ? $set->slug : $dto->slug_suffix;
        }

        Validator::make(['slug' => $slug], [
            'slug' => Rule::unique('product_sets', 'slug')->ignoreModel($set),
        ])->validate();

        if (!($dto->children_ids instanceof Optional)) {
            $children = ProductSet::query()->whereIn('id', $dto->children_ids)->get();
            $this->updateChildren($children, $set->getKey(), $slug, $publicParent && $dto->public);

            $rootOrder = ProductSet::reversed()->first()?->order + 1;

            $productIds = $set->allProductsIds()->merge($set->relatedProducts->pluck('id'));
            $set->children()
                ->whereNotIn('id', $dto->children_ids)
                ->update([
                    'parent_id' => null,
                    'order' => $rootOrder + $order,
                ]);
        }

        $set->fill(
            $dto->toArray() + [
                'order' => $order,
                'slug' => $slug,
                'public_parent' => $publicParent,
            ],
        );

        foreach ($dto->translations as $lang => $translations) {
            $set->setLocale($lang)->fill($translations);
        }

        $set->save();

        if (!($dto->attributes instanceof Optional)) {
            $set->attributes()->sync($dto->attributes);
            $this->reorderAttributes($set, $dto->attributes);
        }

        if (!($dto->seo instanceof Optional)) {
            $this->seoMetadataService->createOrUpdateFor($set, $dto->seo);
        }

        // searchable is handled by the event listener
        ProductSetUpdated::dispatch($set);

        return $set;
    }

    public function reorder(array $sets, ProductSet|null $parent = null): void
    {
        foreach ($sets as $key => $id) {
            ProductSet::query()
                ->where('parent_id', $parent?->getKey()) // update only children of the parent
                ->where('id', $id)
                ->update(['order' => $key]);
        }
    }

    public function attach(ProductSet $set, array $productsIds): Collection
    {
        $currentIds = $set->products->pluck('id')->toArray();
        $toDetach = array_diff($currentIds, $productsIds);
        $toAttach = array_diff($productsIds, $currentIds);

        $set->products()->detach($toDetach);

        /**
         * @var int $index
         * @var Product $product
         */
        foreach ($set->products()->cursor() as $index => $product) {
            $product->pivot->update(['order' => $index]);
        }

        $set->products()->attach($toAttach);
        $set->refresh();

        $productsWithoutOrder = $set->products->filter(fn (Product $product) => $product->pivot->order === null);

        if ($productsWithoutOrder->isNotEmpty()) {
            $this->fixNullOrders(
                $set,
                $productsWithoutOrder,
            );
        }

        return $set->products;
    }

    public function delete(ProductSet $set): void
    {
        if ($set->children()->count() > 0) {
            $set->children->each(fn ($subset) => $this->delete($subset));
        }

        if ($set->delete()) {
            ProductSetDeleted::dispatch($set);
            if ($set->seo !== null) {
                $this->seoMetadataService->delete($set->seo);
            }
        }
    }

    public function products(ProductSet $set): LengthAwarePaginator
    {
        $query = $set->products();

        if (Gate::denies('product_sets.show_hidden')) {
            $query->public();
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }

    public function flattenSetsTree(Collection $sets, string $relation): Collection
    {
        $subsets = $sets->map(
            fn ($set) => $this->flattenSetsTree($set->{$relation}, $relation),
        );

        return $subsets->flatten()->concat($sets->toArray());
    }

    /**
     * Recursive get all parents of set collection if parent exists.
     */
    public function flattenParentsSetsTree(Collection $sets): Collection
    {
        $parents = $sets->map(fn ($set) => $set->parent)->filter(fn ($set) => $set !== null);

        if ($parents->count() === 0) {
            return $sets;
        }

        return $sets->merge($this->flattenParentsSetsTree($parents));
    }

    public function reorderProducts(ProductSet $set, ProductsReorderDto $dto): void
    {
        $productsWithoutOrder = $set->products->filter(fn (Product $product) => $product->pivot->order === null);

        if ($productsWithoutOrder->isNotEmpty()) {
            $this->fixNullOrders(
                $set,
                $productsWithoutOrder,
            );
        }

        if ($this->hasSameOrderProducts($set)) {
            $this->fixSameOrder($set);
        }

        $maxOrder = $set->products->count() - 1;

        $dto->products->each(function (ProductReorderDto $product) use ($set, $maxOrder): void {
            $oldOrder = array_search($product->id, $set->products->pluck('id', 'pivot.order')->all(), true);
            $newOrder = min($product->order, $maxOrder);

            $set->products()->updateExistingPivot($product->id, ['order' => $newOrder]);

            $query = $set->products()->newPivotStatement()
                ->where('product_set_id', $set->id)
                ->where('product_id', '!=', $product->id);

            if ($newOrder > $oldOrder) {
                $query
                    ->where('order', '<=', $newOrder)
                    ->where('order', '>', $oldOrder)
                    ->decrement('order');
            }

            if ($newOrder < $oldOrder) {
                $query
                    ->where('order', '<=', $oldOrder)
                    ->where('order', '>=', $newOrder)
                    ->increment('order');
            }
        });
    }

    public function fixOrderForSets(array $setIds, Product $product): void
    {
        $sets = ProductSet::query()->whereIn('id', $setIds)->with('products');
        foreach ($sets->cursor() as $set) {
            $set->products()->updateExistingPivot($product->getKey(), ['order' => $set->products->count() - 1]);
        }
    }

    private function fixNullOrders(ProductSet $set, Collection $productsWithoutOrder): void
    {
        $existingOrder = $set->products->pluck('pivot.order')->filter(fn (?int $order) => $order !== null);
        $missingOrders = array_diff(range(0, $set->products->count() - 1), $existingOrder->toArray());

        $productsWithoutOrder->each(function (Product $product) use (&$missingOrders): void {
            $product->pivot->update(['order' => array_shift($missingOrders)]);
        });

        $set->refresh();
    }

    private function hasSameOrderProducts(ProductSet $set): bool
    {
        $groupedProducts = $set->products->groupBy('pivot.order');

        return $groupedProducts->contains(fn (Collection $products): bool => $products->count() > 1);
    }

    private function fixSameOrder(ProductSet $set): void
    {
        $set->products->each(function (Product $product, int $index): void {
            $product->pivot->update(['order' => $index]);
        });

        $set->refresh();
    }

    private function prepareSlug(
        bool|Optional $isOverridden,
        Optional|string|null $slugSuffix,
        string $parentSlug,
    ): ?string {
        $slug = $slugSuffix instanceof Optional ? null : $slugSuffix;
        if (!$isOverridden instanceof Optional && !$isOverridden) {
            return "{$parentSlug}-{$slug}";
        }

        return $slug;
    }

    private function reorderAttributes(ProductSet $set, array $attributesIds): void
    {
        foreach ($attributesIds as $index => $attributeID) {
            $set->attributes()->updateExistingPivot($attributeID, ['order' => $index]);
        }
    }
}
