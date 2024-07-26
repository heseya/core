<?php

namespace App\Services\Contracts;

use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use Domain\ProductSchema\Models\Schema\Schema;

interface AvailabilityServiceContract
{
    public function calculateItemAvailability(Item $item): void;

    public function calculateOptionAvailability(Option $option): void;

    public function calculateSchemaAvailability(Schema $schema): void;

    public function calculateProductAvailability(Product $product): void;

    public function getCalculateOptionAvailability(Option $option): array;

    public function getCalculateSchemaAvailability(Schema $schema): array;

    public function getCalculateProductAvailability(Product $product): array;
}
