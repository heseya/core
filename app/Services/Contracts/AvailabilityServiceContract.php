<?php

namespace App\Services\Contracts;

use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;

interface AvailabilityServiceContract
{
    public function calculateAvailabilityOnOrderAndRestock(Item $item): void;

    public function calculateOptionAvailability(Option $option): void;

    public function calculateSchemaAvailability(Schema $schema): void;

    public function calculateProductAvailability(Product $product, Item $item): void;
}
