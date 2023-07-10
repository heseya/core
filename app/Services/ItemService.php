<?php

namespace App\Services;

use App\Dtos\CartItemDto;
use App\Dtos\ItemDto;
use App\Dtos\OrderProductDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Events\ItemUpdatedQuantity;
use App\Exceptions\ClientException;
use App\Models\Item;
use App\Models\Option;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Schema;
use App\Models\User;
use App\Services\Contracts\ItemServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ItemService implements ItemServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
    ) {}

    public function addItemArrays(array $items1, array $items2): array
    {
        $totalItems = $items1;

        foreach ($items2 as $id => $count) {
            $totalItems[$id] = ($totalItems[$id] ?? 0) + $count;
        }

        return $totalItems;
    }

    public function validateItems(array $items): void
    {
        foreach ($items as $id => $count) {
            /** @var Item $item */
            $item = Item::query()->findOr($id, function () use ($id): void {
                throw new ClientException(Exceptions::CLIENT_ITEM_NOT_FOUND, errorArray: ['id' => $id]);
            });

            if ($item->quantity < $count) {
                // TODO dodanie danych do błędu
                throw new ClientException(Exceptions::CLIENT_NOT_ENOUGH_ITEMS);
            }
        }
    }

    public function validateCartItems(array $items): bool
    {
        foreach ($items as $id => $count) {
            /** @var ?Item $item */
            $item = Item::find($id);

            if ($item === null) {
                return false;
            }

            if (
                $item->quantity < $count
                && $item->unlimited_stock_shipping_time === null
                && ($item->unlimited_stock_shipping_date === null
                    || $item->unlimited_stock_shipping_date < Carbon::now())
            ) {
                return false;
            }
        }

        return true;
    }

    public function checkOrderItems(array $items): Collection
    {
        [$products, $selectedItems] = $this->checkItems($items);

        $this->validateItems($selectedItems);

        return $products;
    }

    /**
     * Returns only avaiable products.
     */
    public function checkCartItems(array $items): array
    {
        $selectedItems = [];
        $purchasedProducts = [];
        $cartItemToRemove = [];
        $products = Collection::make();
        $relation = Auth::user() instanceof User ? 'user' : 'app';

        /** @var CartItemDto $item */
        foreach ($items as $item) {
            $product = Product::query()->find($item->getProductId());

            if (!($product instanceof Product)) {
                continue;
            }

            // Checking purchased limit
            if ($product->purchase_limit_per_user !== null) {
                if (array_key_exists($product->getKey(), $purchasedProducts)) {
                    $purchasedCount = $purchasedProducts[$product->getKey()];
                } else {
                    $purchasedCount = OrderProduct::searchByCriteria([
                        $relation => Auth::id(),
                        'product_id' => $product->getKey(),
                        'paid' => true,
                    ])
                        ->sum('quantity');
                    $purchasedProducts[$product->getKey()] = $purchasedCount;
                }

                $available = $product->purchase_limit_per_user - $purchasedCount;

                if ($available <= 0.0) {
                    $cartItemToRemove[] = $item->getCartItemId();
                    continue;
                }
                $quantity = min($available, $item->getQuantity());
                $purchasedProducts[$product->getKey()] += $quantity;
                $item->setQuantity($quantity);
            }

            $schemas = $item->getSchemas();

            $productItems = [];
            /** @var Item $productItem */
            foreach ($product->items as $productItem) {
                $productItems[$productItem->getKey()] = $productItem->pivot->required_quantity * $item->getQuantity();
            }
            $selectedItems = $this->addItemArrays($selectedItems, $productItems);

            /** @var Schema $schema */
            foreach ($product->schemas as $schema) {
                $value = $schemas[$schema->getKey()] ?? null;

                $schema->validate($value);

                if ($value === null) {
                    continue;
                }

                $schemaItems = $schema->getItems($value, $item->getQuantity());
                $selectedItems = $this->addItemArrays($selectedItems, $schemaItems);
            }

            if ($this->validateCartItems($selectedItems)) {
                $products->push($product);
            }
        }

        // Removing cartitems with exceeded limit
        if (count($cartItemToRemove)) {
            /** @var CartItemDto $item */
            $items = array_filter($items, fn ($item) => !in_array($item->getCartItemId(), $cartItemToRemove));
        }

        return [$products, $items];
    }

    public function checkHasItemType(Collection $products, ?bool $physical, ?bool $digital): bool
    {
        $hasPhysical = false;
        $hasDigital = false;

        /** @var Product $product */
        foreach ($products as $product) {
            if ($product->shipping_digital) {
                $hasDigital = true;
            } else {
                $hasPhysical = true;
            }
        }

        $physicalCheck = $physical === null || $hasPhysical === $physical;
        $digitalCheck = $digital === null || $hasDigital === $digital;

        return $physicalCheck && $digitalCheck;
    }

    public function store(ItemDto $dto): Item
    {
        $item = Item::create($dto->toArray());

        if (!($dto->metadata instanceof Missing)) {
            $this->metadataService->sync($item, $dto->metadata);
        }

        ItemCreated::dispatch($item);

        return $item;
    }

    public function update(Item $item, ItemDto $dto): Item
    {
        $item->update($dto->toArray());

        ItemUpdated::dispatch($item);
        if (
            !($dto->unlimited_stock_shipping_date instanceof Missing)
            || !($dto->unlimited_stock_shipping_time instanceof Missing)
        ) {
            ItemUpdatedQuantity::dispatch($item);
        }

        return $item;
    }

    public function destroy(Item $item): void
    {
        if ($item->delete()) {
            ItemDeleted::dispatch($item);
        }
    }

    /**
     * Refresh serchable index on all related products.
     */
    public function refreshSearchable(Item $item): void
    {
        $ids = $item->products()->select('id')->pluck('id');

        /** @var Option $option */
        foreach ($item->options as $option) {
            $productIds = $option->schema?->products()->select('id')->pluck('id');
            $ids->concat($productIds ? $productIds->toArray() : []);
        }

        // @phpstan-ignore-next-line
        Product::whereIn('id', $ids->unique())->searchable();
    }

    private function checkItems(array $items): array
    {
        $selectedItems = [];
        $purchasedProducts = [];
        $products = Collection::make();

        /** @var OrderProductDto $item */
        foreach ($items as $item) {
            $product = Product::findOrFail($item->getProductId());
            $schemas = $item->getSchemas();

            if ($product->purchase_limit_per_user !== null) {
                $purchasedProducts = $this->checkProductPurchaseLimit(
                    $product->getKey(),
                    $product->purchase_limit_per_user,
                    $purchasedProducts,
                    $item->getQuantity(),
                );
            }

            /** @var Schema $schema */
            foreach ($product->schemas as $schema) {
                $value = $schemas[$schema->getKey()] ?? null;

                $schema->validate($value);

                if ($value === null) {
                    continue;
                }

                $schemaItems = $schema->getItems($value, $item->getQuantity());
                $selectedItems = $this->addItemArrays($selectedItems, $schemaItems);
            }
            $products->push($product);
        }

        return [$products, $selectedItems];
    }

    private function checkProductPurchaseLimit(
        string $productId,
        float $limit,
        array $purchasedProducts,
        float $quantity
    ): array {
        $relation = Auth::user() instanceof User ? 'user' : 'app';
        if (array_key_exists($productId, $purchasedProducts)) {
            $quantity += $purchasedProducts[$productId];
        } else {
            $quantity += OrderProduct::searchByCriteria([
                $relation => Auth::id(),
                'product_id' => $productId,
                'paid' => true,
            ])
                ->sum('quantity');
        }

        if ($limit < $quantity) {
            throw new ClientException(Exceptions::PRODUCT_PURCHASE_LIMIT, errorArray: ['id' => $productId, 'limit' => $limit]);
        }

        $purchasedProducts[$productId] = $quantity;

        return $purchasedProducts;
    }
}
