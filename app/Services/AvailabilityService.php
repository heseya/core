<?php

namespace App\Services;

use App\Enums\SchemaType;
use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\AvailabilityServiceContract;
use Illuminate\Support\Collection;

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
            ->get();
        $schemas->each(fn ($schema) => $this->calculateSchemaAvailability($schema));

        $products = $schemas->pluck('products')->flatten();
        $products->each(fn ($product) => $this->calculateProductAvailability($product, $item));
    }

    public function calculateOptionAvailability(Option $option): void
    {
        if ($option->available && $option->items->every(fn ($item) => $item->quantity <= 0)) {
            $option->update([
                'available' => false,
            ]);
        } elseif (!$option->available && $option->items->some(fn ($item) => $item->quantity > 0)) {
            $option->update([
                'available' => true,
            ]);
        }
    }

    public function calculateSchemaAvailability(Schema $schema): void
    {
        if ($schema->available && $schema->options->every(fn ($option) => !$option->available)) {
            $schema->update([
                'available' => false,
            ]);
        } elseif (!$schema->available && $schema->options->some(fn ($option) => $option->available)) {
            $schema->update([
                'available' => true,
            ]);
        }
    }

    public function calculateProductAvailability(Product $product, Item $item): void
    {
        if (!$product->schemas()->exists()){
            $product->update([
                'available' => true,
            ]);
            return;
        }

        if ($product->getKey() === '0002') {
            dd(123);
        }

        $requiredSelectSchemas = $product->requiredSchemas->where('type.value', SchemaType::SELECT);

        $items = new Collection();

        $requiredSelectSchemas->each(function ($schema) use ($items) {
           $schema->options->each(function ($option) use ($items) {
               $items->push($option->items);
           });
        });

        $items = $items->flatten()->groupBy('id');

        $isProductAvailable = $items->get($item->getKey())->count() <= $item->quantity;

        if ($isProductAvailable) {
            $product->update([
                'available' => true
            ]);
        }
        else {
            $product->update([
                'available' => false
            ]);
        }
    }
}
