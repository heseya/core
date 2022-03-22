<?php

namespace App\Services\Contracts;

use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use Illuminate\Support\Collection;

interface AvailabilityServiceContract
{
    public function calculateAvailabilityOnOrderAndRestock(Item $item): void;

    public function calculateOptionAvailability(Option $option): void;

    public function calculateSchemaAvailability(Schema $schema): void;

    public function calculateProductAvailability(Product $product): void;

    public function checkPermutations(Collection $schemas): bool;

    public function getSchemaOptions(
        Schema $schema,
        Collection $schemas,
        Collection $options,
        int $max,
        int $index = 0
    ): bool;

    public function isOptionsItemsAvailable(Collection $options): bool;
}
