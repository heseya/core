<?php

namespace App\Services;

use App\Enums\SchemaType;
use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService implements AvailabilityServiceContract
{
    public function __construct(protected DepositServiceContract $depositService)
    {
    }

    /**
     * Function used to calculate "available" field for Options, Schemas and Products related to Item step by step
     * used after changing Item's quantity, usually after including Item in Order or creating Deposit
     * containing this item.
     *
     * @param Item $item
     *
     * @return void
     */
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

        $item->products->each(fn ($product) => $this->calculateProductAvailability($product));
    }

    public function calculateOptionAvailability(Option $option): void
    {
        if ($option->available && $option->items->some(
            fn ($item) => $item->quantity <= 0 && is_null($item->unlimited_stock_shipping_time) &&
                (is_null($item->unlimited_stock_shipping_date) ||
                    $item->unlimited_stock_shipping_date < Carbon::now())
        )) {
            $option->update([
                'available' => false,
                'shipping_time' => null,
                'shipping_date' => null,
            ]);
        } elseif (!$option->available && $option->items->every(
            fn ($item) => $item->quantity > 0 || !is_null($item->unlimited_stock_shipping_time) ||
                (!is_null($item->unlimited_stock_shipping_date) &&
                    $item->unlimited_stock_shipping_date >= Carbon::now())
        )) {
            $option->update([
                'available' => true,
            ] + $this->depositService->getMaxShippingTimeDateForItems($option->items));
        }
    }

    public function calculateSchemaAvailability(Schema $schema): void
    {
        if (!$schema->required) {
            $schema->update([
                'available' => true,
                'shipping_time' => null,
                'shipping_date' => null,
            ]);
        }
        if ($schema->available && $schema->options->every(fn ($option) => !$option->available)) {
            $schema->update([
                'available' => false,
                'shipping_time' => null,
                'shipping_date' => null,
            ]);
        } elseif (!$schema->available && $schema->options->some(fn ($option) => $option->available)) {
            $schema->update([
                'available' => true,
            ] + $this->depositService->getMinShippingTimeDateForOptions($schema->options));
        }
    }

    public function calculateProductAvailability(Product $product): void
    {
        $product->update([
            'available' => $this->isProductAvaiable($product),
        ] + $this->depositService->getProductShippingTimeDate($product));
    }

    public function isProductAvaiable(Product $product): bool
    {
        //If every product's item quantity is greater or equal to pivot quantity or product has no schemas
        //then product is available
        $requiredSelectSchemas = $product->requiredSchemas->where('type.value', SchemaType::SELECT);
        if ($requiredSelectSchemas->isEmpty() || (
                $product->items->isNotEmpty() &&
                $product->items->every(
                    fn (Item $item) => $item->pivot->required_quantity <= $item->quantity ||
                        !is_null($item->unlimited_stock_shipping_time) ||
                        (!is_null($item->unlimited_stock_shipping_date) &&
                            $item->unlimited_stock_shipping_date >= Carbon::now())
                )
        )) {
            return true;
        }

        $hasAvailablePermutations = $this->checkPermutations($requiredSelectSchemas);

        if ($hasAvailablePermutations) {
            return true;
        }

        return false;
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
                if ($this->isOptionsItemsAvailable($options)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isOptionsItemsAvailable(Collection $options): bool
    {
        $items = $options->pluck('items')->flatten()->groupBy('id');

        return $items->every(fn ($item) => $item->first()->quantity >= $items->get($item->first()->getKey())->count());
    }
}
