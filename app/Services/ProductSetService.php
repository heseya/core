<?php

namespace App\Services;

use App\DTO\ProductSet\ProductSetCreateDto;
use App\DTO\ProductSet\ProductSetUpdateDto;
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
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ProductSetService implements ProductSetServiceContract
{
    public function __construct(
        private SeoMetadataServiceContract $seoMetadataService,
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

        $set = new ProductSet($dto->toArray() + [
            'order' => $order,
            'slug' => $slug,
            'public_parent' => $publicParent,
        ]);

        foreach ($dto->translations as $lang => $translations) {
            $set->setLocale($lang)->fill($translations);
        }

        $set->save();
        $set->attributes()->sync($dto->attributes);

        $children = Collection::make($dto->children_ids);
        if ($children->isNotEmpty()) {
            $children = $children->map(fn ($id) => ProductSet::query()->findOrFail($id));
            $this->updateChildren($children, $set->getKey(), $slug, $publicParent && $dto->public);
        }

        if (!($dto->seo instanceof Optional)) {
            $this->seoMetadataService->createOrUpdateFor($set, $dto->seo);
        }

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync($set, $dto->metadata);
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
        bool $publicParent
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
            $slug = $this->prepareSlug($dto->slug_override, $dto->slug_suffix, $parent->slug);
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

            $set->children()
                ->whereNotIn('id', $dto->children_ids)
                ->update([
                    'parent_id' => null,
                    'order' => $rootOrder + $order,
                ]);
        }

        $set->fill($dto->toArray() + [
            'order' => $order,
            'slug' => $slug,
            'public_parent' => $publicParent,
        ]);

        foreach ($dto->translations as $lang => $translations) {
            $set->setLocale($lang)->fill($translations);
        }

        $set->save();

        if (!($dto->attributes instanceof Optional)) {
            $set->attributes()->sync($dto->attributes);
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
        $set->products()->sync($productsIds);

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

    // TODO: fix this
    public function reorderProducts(ProductSet $set, ProductsReorderDto $dto): void
    {
        if (!$dto->getProducts() instanceof Missing) {
            /** @var Product $product */
            $product = $set->products()->where('id', $dto->getProducts()[0]['id'])->firstOrFail();
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

    private function prepareSlug(
        bool|Optional $isOverridden,
        Optional|string|null $slugSuffix,
        string $parentSlug
    ): ?string {
        $slug = $slugSuffix instanceof Optional ? null : $slugSuffix;
        if (!$isOverridden instanceof Optional && !$isOverridden) {
            return "{$parentSlug}-{$slug}";
        }

        return $slug;
    }
}
