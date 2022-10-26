<?php

namespace Tests\Unit\Availability;

use App\Models\Item;
use App\Models\Option;
use Illuminate\Support\Collection;

trait ItemCreate
{
    /**
     * Crate array of Items with pivot. You can use required_quantity param.
     */
    protected function createItems(array $items): Collection
    {
        $collection = Collection::make();

        foreach ($items as $attributes) {
            $item = new Item($attributes);
            $attributes['required_quantity'] ??= 1; // database has default value of 1
            $pivot = $item->newPivot($item, $attributes, 'carts', true);
            $item->setRelation('pivot', $pivot);

            $collection->push($item);
        }

        return $collection;
    }

    protected function createOption(array $relatedItems = []): Option
    {
        $option = new Option();
        $option->setRelation('items', $this->createItems($relatedItems));

        return $option;
    }
}
