<?php

namespace Tests\Unit\Availability;

use App\Models\Item;
use App\Models\Option;
use App\Models\Schema;
use App\Services\AvailabilityService;
use App\Services\DepositService;
use Illuminate\Support\Collection;

trait AvailabilityUntiles
{
    protected AvailabilityService $availabilityService;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->availabilityService = new AvailabilityService(new DepositService());
        Item::disableAuditing();
    }

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

    protected function createOption(array $relatedItems = [], array $arguments = []): Option
    {
        $option = new Option($arguments);
        $option->setRelation('items', $this->createItems($relatedItems));

        return $option;
    }

    protected function createSchema(array $arguments, array $options = []): Schema
    {
        $schema = new Schema($arguments);
        $schema->setRelation('options', $options);

        return $schema;
    }
}
