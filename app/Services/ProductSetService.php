<?php

namespace App\Services;

use App\Dtos\ProductSetDto;
use App\Dtos\ProductSetUpdateDto;
use App\Dtos\ProductsReorderDto;
use App\Events\ProductSetCreated;
use App\Events\ProductSetDeleted;
use App\Events\ProductSetUpdated;
use App\Models\Product;
use App\Models\ProductSet;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\ProductSetServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductSetService implements ProductSetServiceContract
{
    public function __construct(
        private SeoMetadataServiceContract $seoMetadataService,
        private MetadataServiceContract $metadataService,
    ) {
    }

    public function authorize(ProductSet $set): void
    {
        if (
            Gate::denies('product_sets.show_hidden') &&
            !ProductSet::public()->where('id', $set->getKey())->exists()
        ) {
            throw new NotFoundHttpException();
        }
    }

    public function searchAll(array $attributes, bool $root): LengthAwarePaginator
    {
        $query = ProductSet::searchByCriteria($attributes)
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

        /** @var ProductSet $set */
        $set = ProductSet::create($dto->toArray() + [
            'order' => $order,
            'slug' => $slug,
            'public_parent' => $publicParent,
        ]);

        $attributes = Collection::make($dto->getAttributesIds());
        if ($attributes->isNotEmpty()) {
            $set->attributes()->sync($attributes);
        }

        $children = Collection::make($dto->getChildrenIds());
        if ($children->isNotEmpty()) {
            $children = $children->map(fn ($id) => ProductSet::findOrFail($id));
            $this->updateChildren($children, $set->getKey(), $slug, $publicParent && $dto->isPublic());
        }

        $set->seo()->save($this->seoMetadataService->create($dto->getSeo()));

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($set, $dto->getMetadata());
        }

        // searchable is handled by the event listener
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
            function ($child, $order) use ($parentId, $parentSlug, $publicParent): void {
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

    public function update(ProductSet $set, ProductSetUpdateDto $dto): ProductSet
    {
        if ($dto->getParentId() !== null) {
            $parent = ProductSet::findOrFail($dto->getParentId());

            if ($set->parent_id !== $dto->getParentId()) {
                $lastChild = $parent->children()->reversed()->first();
                $order = $lastChild ? $lastChild->order + 1 : 0;
            } else {
                $order = $set->order;
            }

            $publicParent = $parent->public && $parent->public_parent;
            $slug = $dto->isSlugOverridden() ? $dto->getSlugSuffix() :
                $parent->slug . '-' . $dto->getSlugSuffix();
        } else {
            if ($set->parent_id !== null) {
                $last = ProductSet::reversed()->first();
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

        $children = ProductSet::whereIn('id', $dto->getChildrenIds())->get();
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

        if (!($dto->getAttributesIds() instanceof Missing)) {
            $attributes = Collection::make($dto->getAttributesIds());
            $set->attributes()->sync($attributes);
        }

        if ($set->seo !== null) {
            $this->seoMetadataService->update($dto->getSeo(), $set->seo);
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
        // old products for reindexing
        $oldProductsIds = $set->products()->pluck('id');

        $set->products()->sync($productsIds);

        // @phpstan-ignore-next-line
        Product::query()->whereIn(
            'id',
            $oldProductsIds->merge($productsIds)->unique(),
        )->searchable();

        return $set->products;
    }

    public function delete(ProductSet $set): void
    {
        if ($set->children()->count() > 0) {
            $set->children->each(fn ($subset) => $this->delete($subset));
        }

        $productsIds = $set->allProductsIds();

        if ($set->delete()) {
            ProductSetDeleted::dispatch($set);
            if ($set->seo !== null) {
                $this->seoMetadataService->delete($set->seo);
            }
        }

        // @phpstan-ignore-next-line
        Product::query()->whereIn('id', $productsIds)->searchable();
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
            fn ($set) => $this->flattenSetsTree($set->$relation, $relation),
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
        $product = $set->products()->where('id', $dto->getProducts()[0]['id'])->first();
        $order = $dto->getProducts()[0]['order'];
        $orderedProductsAmount = $set->products()
            ->whereNotNull('product_set_product.order')
            ->whereNot('product_id', $dto->getProducts()[0]['id'])
            ->count();

        if ($order > $orderedProductsAmount) {
            $order = $orderedProductsAmount;
        }

        if ($product->pivot->order === null) {
            $this->setOrder($order);
        } else {
            if ($order < $product->pivot->order) {
                $this->setHigherOrder($product, $order);
            } else {
                $this->setLowerOrder($product, $order);
            }
        }

        $product->pivot->order = $order;
        $product->pivot->save();

        /** @var int $highestOrder */
        $highestOrder = $set->products->max('pivot.order');

        $this->assignOrderToNulls($highestOrder, $set->products->whereNull('pivot.order'));
    }

    public function indexAllProducts(ProductSet $set): void
    {
        // @phpstan-ignore-next-line
        Product::query()->whereIn('id', $set->allProductsIds())->searchable();
    }

    private function setHigherOrder(Product $product, int $order): void
    {
        DB::table('product_set_product')->where([
            ['order', '>=', $order],
            ['order', '<', $product->pivot->order],
        ])
            ->increment('order');
    }

    private function setLowerOrder(Product $product, int $order): void
    {
        DB::table('product_set_product')->where([
            ['order', '<=', $order],
            ['order', '>', $product->pivot->order],
        ])
            ->decrement('order');
    }

    private function setOrder(int $order): void
    {
        DB::table('product_set_product')->where([
            ['order', '>=', $order],
        ])
            ->increment('order');
    }

    private function assignOrderToNulls(int $highestOrder, Collection $products): void
    {
        $products->each(function (Product $product) use (&$highestOrder): void {
            ++$highestOrder;
            $product->pivot->order = $highestOrder;
            $product->pivot->save();
        });
    }
}
