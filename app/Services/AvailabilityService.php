<?php

namespace App\Services;

use App\Enums\SchemaType;
use App\Events\ProductUpdated;
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

        $products = $schemas->pluck('products')->flatten()->unique('id');
        $products->each(fn ($product) => $this->calculateProductAvailability($product));
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
        if (!$schema->required) {
            $schema->update([
                'available' => true,
            ]);
        }
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

    public function calculateProductAvailability(Product $product): void
    {
        if (!$product->schemas()->exists()) {
            $product->update([
                'available' => true,
            ]);

            return;
        }

        $requiredSelectSchemas = $product->requiredSchemas->where('type.value', SchemaType::SELECT);
        if ($requiredSelectSchemas->count() > 0) {
            $hasAvailablePermutations = $this->checkPermutations($requiredSelectSchemas);
            if ($hasAvailablePermutations) {
                $product->update([
                    'available' => true,
                ]);
            } else {
                $product->update([
                    'available' => false,
                ]);
            }

            if ($product->wasChanged('available')) {
                ProductUpdated::dispatch($product);
            }
        }
    }

    public function checkPermutations(Collection $schemas): bool
    {
        $max = $schemas->count();
        $options = new Collection();
        return $this->getSchemaOptions($schemas->first(), $schemas, $options, $max);
    }

    public function getSchemaOptions(
        Schema $schema,
        Collection $schemas,
        Collection $options,
        int $max,
        int $index = 0
    ): bool {
        foreach ($schema->options as $option) {
            $options->put($schema->getKey(), $option);
            if ($index < $max - 1) {
                $newIndex = $index + 1;

                return $this->getSchemaOptions($schemas->get($newIndex), $schemas, $options, $max, $newIndex);
            }
            if ($index === $max - 1) {
                if ($this->checkIfOptionsItemsAreAvailable($options)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function checkIfOptionsItemsAreAvailable(Collection $options): bool
    {
        $items = $options->pluck('items')->flatten()->groupBy('id');
        return $items->every(fn ($item) => $item->first()->quantity >= $items->get($item->first()->getKey())->count());
    }
}
