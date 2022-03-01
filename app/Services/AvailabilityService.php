<?php

namespace App\Services;

use App\Enums\SchemaType;
use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\AvailabilityServiceContract;

class AvailabilityService implements AvailabilityServiceContract
{
    public function __construct()
    {
    }

    public function calculateAvailabilityOnOrderAndRestock(Item $item): void
    {
        $options = $item->options;
        $options->each(fn ($option) => $this->calculateOptionAvailability($option));

        $schemas = Schema::where('type', SchemaType::SELECT)
            ->whereIn('id', $options->pluck('schema_id'))
            ->with('products')
            ->get();
        $schemas->each(fn ($schema) => $this->calculateSchemaAvailability($schema));

        $products = $schemas->pluck('products')->flatten();
        $products->each(fn ($product) => $this->calculateProductAvailability($product));
    }

    public function calculateOptionAvailability(Option $option): void
    {
        if ($option->available && $option->items->every(fn ($item) => $item->quantity <= 0)) {
            $option->update([
                'available' => 0,
            ]);
        } elseif (!$option->available && $option->items->some(fn ($item) => $item->quantity > 0)) {
            $option->update([
                'available' => 1,
            ]);
        }
    }

    public function calculateSchemaAvailability(Schema $schema): void
    {
        if ($schema->available && $schema->options->every(fn ($option) => !$option->available)) {
            $schema->update([
                'available' => 0,
            ]);
        } elseif (!$schema->available && $schema->options->some(fn ($option) => $option->available)) {
            $schema->update([
                'available' => 1,
            ]);
        }
    }

    public function calculateProductAvailability(Product $product): void
    {
        if ($product->available
            && $product->schemas()->exists()
            && ($product->requiredSchemas->some(fn ($schema) => !$schema->available)
                || $product->schemas->every(fn ($schema) => !$schema->available))) {
            $product->update([
                'available' => 0,
            ]);
        } elseif (!$product->available
            && (!$product->schemas()->exists()
                || $product->requiredSchemas->every(fn ($schema) => $schema->available))) {
            $product->update([
                'available' => 1,
            ]);
        }
    }
}
