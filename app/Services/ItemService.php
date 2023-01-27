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
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\ItemServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ItemService implements ItemServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
    ) {
    }

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
            /** @var ?Item $item */
            $item = Item::query()->findOr($id, function () use ($id): void {
                throw new ClientException(Exceptions::CLIENT_ITEM_NOT_FOUND, errorArray: [
                    'id' => $id,
                ]);
            });

            if ($item->quantity < $count) {
                //TODO dodanie danych do błędu
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
                $item->quantity < $count &&
                is_null($item->unlimited_stock_shipping_time) &&
                (is_null($item->unlimited_stock_shipping_date) ||
                    $item->unlimited_stock_shipping_date < Carbon::now())
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

    public function checkCartItems(array $items): Collection
    {
        $selectedItems = [];
        $products = Collection::make();

        /** @var CartItemDto $item */
        foreach ($items as $item) {
            $product = Product::find($item->getProductId());

            if ($product === null) {
                continue;
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

        return $products;
    }

    public function store(ItemDto $dto): Item
    {
        $item = Item::create($dto->toArray());

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($item, $dto->getMetadata());
        }

        ItemCreated::dispatch($item);

        return $item;
    }

    public function update(Item $item, ItemDto $dto): Item
    {
        $item->update($dto->toArray());

        ItemUpdated::dispatch($item);
        if (
            !($dto->getUnlimitedStockShippingDate() instanceof Missing) ||
            !($dto->getUnlimitedStockShippingTime() instanceof Missing)
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

        foreach ($item->options as $option) {
            /** @var Option $option */
            $ids->concat($option->schema->products()->select('id')->pluck('id')->toArray());
        }

        // @phpstan-ignore-next-line
        Product::whereIn('id', $ids->unique())->searchable();
    }

    private function checkItems(array $items): array
    {
        $selectedItems = [];
        $products = Collection::make();

        /** @var OrderProductDto|CartItemDto $item */
        foreach ($items as $item) {
            $product = Product::findOrFail($item->getProductId());
            $schemas = $item->getSchemas();

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
}
