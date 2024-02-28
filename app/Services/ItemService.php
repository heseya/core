<?php

namespace App\Services;

use App\Dtos\CartItemDto;
use App\Dtos\CartItemErrorDto;
use App\Dtos\CartUnavailableItemDto;
use App\Dtos\ItemDto;
use App\Dtos\OrderProductDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Events\ItemUpdatedQuantity;
use App\Exceptions\ClientException;
use App\Models\Item;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Schema;
use App\Models\User;
use App\Services\Contracts\ItemServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use Domain\Metadata\Models\Metadata;
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

    public function substractItemArrays(array $items1, array $items2): array
    {
        $totalItems = $items1;

        foreach ($items2 as $id => $count) {
            if (isset($totalItems[$id])) {
                $totalItems[$id] -= $count;
                if ($totalItems[$id] < 0) {
                    unset($totalItems[$id]);
                }
            }
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
        $unavailableItems = [];

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
                if ($available !== $item->getQuantity()) {
                    $unavailableItems[] = new CartUnavailableItemDto($item->getCartItemId(), $item->getQuantity() - $quantity, new CartItemErrorDto(Exceptions::PRODUCT_PURCHASE_LIMIT->name, Exceptions::PRODUCT_PURCHASE_LIMIT->value));
                }
                $item->setQuantity($quantity);
            }

            $schemas = $item->getSchemas();

            $productItems = [];
            $itemsForOneProduct = [];
            /** @var Item $productItem */
            foreach ($product->items as $productItem) {
                $productItems[$productItem->getKey()] = $productItem->pivot->required_quantity * $item->getQuantity();
                $itemsForOneProduct[$productItem->getKey()] = $productItem->pivot->required_quantity;
            }
            $selectedItems = $this->addItemArrays($selectedItems, $productItems);

            $currentProductItems = $this->addItemArrays([], $productItems);
            /** @var Schema $schema */
            foreach ($product->schemas as $schema) {
                $value = $schemas[$schema->getKey()] ?? null;

                $schema->validate($value);

                if ($value === null) {
                    continue;
                }

                $itemsForOneProduct = $this->addItemArrays($itemsForOneProduct, $schema->getItems($value, 1));
                $schemaItems = $schema->getItems($value, $item->getQuantity());
                $selectedItems = $this->addItemArrays($selectedItems, $schemaItems);
                $currentProductItems = $this->addItemArrays($currentProductItems, $schemaItems);
            }

            if ($this->validateCartItems($selectedItems)) {
                $products->push($product);
            } else {
                if ($item->getQuantity() > 1) {
                    $availableItemsFromCurrentProduct = $this->calculateAvailableItems($currentProductItems);
                    $itemsDiff = $this->substractItemArrays($currentProductItems, $this->substractItemArrays($selectedItems, $availableItemsFromCurrentProduct));
                    $maxQuantity = $this->calculateCurrentProductAvailableQuantity($item->getQuantity(), $itemsDiff, $itemsForOneProduct);
                    $selectedItems = $this->substractItemArrays($selectedItems, $currentProductItems);
                    if ($maxQuantity > 0) {
                        $selectedItems = $this->addItemArrays($selectedItems, $this->calculateItemQuantityForProduct($itemsForOneProduct, $maxQuantity));
                        $itemQuantity = $item->getQuantity() - $maxQuantity;
                        $unavailableItems[] = new CartUnavailableItemDto($item->getCartItemId(), $itemQuantity, new CartItemErrorDto(Exceptions::PRODUCT_NOT_ENOUGH_ITEMS_IN_WAREHOUSE->name, Exceptions::PRODUCT_NOT_ENOUGH_ITEMS_IN_WAREHOUSE->value));
                        $item->setQuantity($maxQuantity);
                        $products->push($product);
                    } else {
                        $cartItemToRemove[] = $item->getCartItemId();
                        $unavailableItems[] = new CartUnavailableItemDto($item->getCartItemId(), $item->getQuantity(), new CartItemErrorDto(Exceptions::PRODUCT_NOT_ENOUGH_ITEMS_IN_WAREHOUSE->name, Exceptions::PRODUCT_NOT_ENOUGH_ITEMS_IN_WAREHOUSE->value));
                    }
                } else {
                    $cartItemToRemove[] = $item->getCartItemId();
                    $selectedItems = $this->substractItemArrays($selectedItems, $currentProductItems);
                }
            }
        }

        // Removing cartitems with exceeded limit
        if (count($cartItemToRemove)) {
            /** @var CartItemDto $item */
            $items = array_filter($items, fn ($item) => !in_array($item->getCartItemId(), $cartItemToRemove));
        }

        return [$products, $items, $unavailableItems];
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

    public function syncProductItems(Product $product, string $metadata): void
    {
        /** @var Metadata $externalId */
        $externalId = $product->metadata()->where('name', '=', $metadata)->first();

        $item = Item::query()->firstOrCreate(['sku' => $externalId->value], ['name' => $product->name]);
        $productItem = $product->items()->where('id', '=', $item->getKey())->first();

        if (!$productItem) {
            $product->items()->attach($item->getKey(), ['required_quantity' => 1]);
        }
    }

    private function checkItems(array $items): array
    {
        $selectedItems = [];
        $purchasedProducts = [];
        $products = Collection::make();

        /** @var OrderProductDto $item */
        foreach ($items as $item) {
            /** @var Product $product */
            $product = Product::query()->findOrFail($item->getProductId());
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

    /**
     * @throws ClientException
     */
    private function checkProductPurchaseLimit(
        string $productId,
        float $limit,
        array $purchasedProducts,
        float $quantity,
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

    /**
     * @param array<string, float> $productItems
     *
     * @return array<string, float>
     */
    private function calculateAvailableItems(array $productItems): array
    {
        $result = [];
        foreach ($productItems as $itemId => $quantity) {
            /** @var Item|null $item */
            $item = Item::query()->find($itemId);
            $result[$itemId] = $item ? $item->quantity : 0;
        }

        return $result;
    }

    /**
     * @param array<string, float> $maxAvailableItems
     * @param array<string, float> $oneProductItems
     */
    private function calculateCurrentProductAvailableQuantity(float $maxQuantity, array $maxAvailableItems, array $oneProductItems): int
    {
        return $this->calculateQuantity($oneProductItems, $maxAvailableItems, $maxQuantity);
    }

    /**
     * @param array<string, float> $oneProductItems
     * @param array<string, float> $maxAvailableItems
     */
    private function calculateQuantity(array $oneProductItems, array $maxAvailableItems, float $cartQuantity): int
    {
        $quantity = (int) $cartQuantity;
        foreach ($maxAvailableItems as $item => $availableItem) {
            $available = (int) ($availableItem / $oneProductItems[$item]);
            if ($available === 0) {
                return $available;
            }
            $quantity = min($available, $quantity);
        }

        return $quantity;
    }

    /**
     * @param array<string, float> $productItems
     *
     * @return array<string, float>
     */
    private function calculateItemQuantityForProduct(array $productItems, float $quantity): array
    {
        foreach ($productItems as $item => $q) {
            $productItems[$item] = $q * $quantity;
        }

        return $productItems;
    }
}
