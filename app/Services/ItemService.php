<?php

namespace App\Services;

use App\Dtos\CartItemDto;
use App\Dtos\OrderProductDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Item;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\ItemServiceContract;
use Illuminate\Support\Collection;

class ItemService implements ItemServiceContract
{
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
            $item = Item::find($id);

            if ($item === null) {
                throw new ClientException("Item `{$id}` not found");
            }

            if ($item->quantity < $count) {
                //TODO dodanie danych do błędu
                throw new ClientException(Exceptions::CLIENT_NOT_ENOUGH_ITEMS);
            }
        }
    }

    public function validateCartItems(array $items, array $selectedItems): bool
    {
        foreach ($items as $id => $count) {
            /** @var ?Item $item */
            $item = Item::find($id);

            if ($item === null) {
                return false;
            }

            if (array_key_exists($id, $selectedItems)) {
                $count += $selectedItems[$id];
            }

            if ($item->quantity < $count) {
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
            $product = Product::findOrFail($item->getProductId());
            $schemas = $item->getSchemas();
            $available = true;

            /** @var Schema $schema */
            foreach ($product->schemas as $schema) {
                $value = $schemas[$schema->getKey()] ?? null;

                $schema->validate($value);

                if ($value === null) {
                    continue;
                }

                $schemaItems = $schema->getItems($value, $item->getQuantity());
                // Walidacja schemaItems z uwzględnieniem selectedItems
                if (!$this->validateCartItems($schemaItems, $selectedItems)) {
                    $available = false;
                    break;
                }
                $selectedItems = $this->addItemArrays($selectedItems, $schemaItems);
            }

            if ($available) {
                $products->push($product);
            }
        }

        return $products;
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
