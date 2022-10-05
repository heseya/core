<?php

namespace App\Services\Contracts;

use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use Illuminate\Support\Collection;

interface AvailabilityServiceContract
{
    public function calculateAvailabilityOnAllItemRelations(Item $item): void;

    public function calculateOptionAvailability(Option $option): void;

    public function calculateSchemaAvailability(Schema $schema): void;

    public function calculateProductAvailability(Product $product): void;

    public function isProductAvailable(Product $product): bool;

    public function checkPermutations(Collection $schemas, array $items): bool;

    public function getSchemaOptions(
        Schema $schema,
        Collection $schemas,
        Collection $options,
        int $max,
        array $items,
        int $index = 0
    ): bool;

    public function isOptionsItemsAvailable(Collection $options, array $items): bool;
}
