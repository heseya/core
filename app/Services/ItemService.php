<?php

namespace App\Services;

use App\Exceptions\ItemException;
use App\Models\Item;
use App\Services\Contracts\ItemServiceContract;

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
            /** @var Item $item */
            $item = Item::findOrFail($id);

            if ($item->quantity < $count) {
                throw new ItemException(
                    "There's less than ${count} of {$item->name} available",
                );
            }
        }
    }
}
