<?php

namespace App\Services;

use App\Enums\SchemaType;
use App\Events\ProductUpdated;
use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService implements AvailabilityServiceContract
{
    public function __construct(
        protected DepositServiceContract $depositService,
    ) {
    }

    /**
     * Function used to calculate "available" field for Options, Schemas and Products related to Item step by step
     * used after changing Item's quantity, usually after including Item in Order or creating Deposit
     * containing this item.
     */
    public function calculateAvailabilityOnOrderAndRestock(Item $item): void
    {
        // Options
        $options = $item->options;
        $options->each(fn (Option $option) => $this->calculateOptionAvailability($option));

        // Schemas
        $schemas = Schema::query()
            ->where('type', SchemaType::SELECT)
            ->whereIn('id', $options->pluck('schema_id'))
            ->get();
        $schemas->each(fn (Schema $schema) => $this->calculateSchemaAvailability($schema));

        // Products
        $schemas
            ->pluck('products')
            ->flatten()
            ->merge($item->products)
            ->unique('id')
            ->each(fn (Product $product) => $this->calculateProductAvailability($product));
    }

    public function calculateOptionAvailability(Option $option): void
    {
        $option->update($this->getCalculateOptionAvailability($option));
    }

    public function calculateSchemaAvailability(Schema $schema): void
    {
        $schema->update($this->getCalculateSchemaAvailability($schema));
    }

    public function calculateProductAvailability(Product $product): void
    {
        $product->productAvailabilities()->delete();
        $product->update($this->getCalculateProductAvailability($product));

        if ($product->wasChanged()) {
            ProductUpdated::dispatch($product);
        }
    }

    /**
     * @return array{available: bool, shipping_time: (int|null), shipping_date: (Carbon|null)}
     */
    public function getCalculateOptionAvailability(Option $option): array
    {
        // If option don't have any related items is always avaiable
        $available = true;
        $shipping_time = null;
        $shipping_date = null;

        foreach ($option->items as $item) {
            // If item required quantity is satisfied by limited stocks
            if ($item->quantity >= $item->pivot->required_quantity) {
                // If item doesn't have shipping time or date
                if ($item->shipping_time === null && $item->shipping_date === null) {
                    continue;
                }

                if ($item->shipping_time > $shipping_time) {
                    $shipping_time = $item->shipping_time;
                }

                if ($item->shipping_date === null) {
                    continue;
                }
            }

            if ($item->unlimited_stock_shipping_time !== null) {
                if ($item->unlimited_stock_shipping_time > $shipping_time) {
                    $shipping_time = $item->unlimited_stock_shipping_time;
                }
                continue;
            }

            if ($item->quantity >= $item->pivot->required_quantity) {
                $shipping_date = $this->compareShippingDate(
                    $shipping_date,
                    $item->shipping_date,
                );
                continue;
            }

            if ($item->unlimited_stock_shipping_date !== null) {
                $shipping_date = $this->compareShippingDate(
                    $shipping_date,
                    $item->unlimited_stock_shipping_date,
                );
                continue;
            }

            return [
                'available' => false,
                'shipping_time' => null,
                'shipping_date' => null,
            ];
        }

        return [
            'available' => $available,
            'shipping_time' => $shipping_time,
            'shipping_date' => $shipping_date,
        ];
    }

    /**
     * @return array{available: bool, shipping_time: (int|null), shipping_date: (Carbon|null)}
     */
    public function getCalculateSchemaAvailability(Schema $schema): array
    {
        if ($schema->type->isNot(SchemaType::SELECT)) {
            return [
                'available' => true,
                'shipping_time' => null,
                'shipping_date' => null,
            ];
        }

        // If option don't have any related options is always unavailable
        $available = false;
        $shipping_time = null;
        $shipping_date = null;

        foreach ($schema->options as $option) {
            $availability = $this->getCalculateOptionAvailability($option);
            if ($option->disabled || !$availability['available']) {
                continue;
            }

            $available = true;
            if ($shipping_time === null || $shipping_time > $availability['shipping_time']) {
                $shipping_time = $availability['shipping_time'];
            }
            $shipping_date = $this->compareShippingDate(
                $shipping_date,
                $availability['shipping_date'],
                true,
            );
        }

        return [
            'available' => $available,
            'shipping_time' => $shipping_time,
            'shipping_date' => $shipping_date,
        ];
    }

    public function getCalculateProductAvailability(Product $product): array
    {
        $quantityStep = $product->quantity_step ?? 1;
        $requiredSchemas = $this->getRequiredSchemasWithItems($product);

        // simple return when no required schemas and items
        if ($requiredSchemas->isEmpty() && $product->items->isEmpty()) {
            return $this->returnProductAvailability(true);
        }

        $items = $this->getAllRequiredItems($product, $requiredSchemas);

        // check only permutation when product don't have required schemas
        if ($requiredSchemas->isEmpty()) {
            return $this->checkProductPermutation($quantityStep, $items, $product->items);
        }

        $available = true;
        $quantity = 0;
        $shipping_time = null;
        $shipping_date = null;
        $productAvailabilities = [];

        $permutations = Collection::make($requiredSchemas->first()->options);
        $requiredSchemas->shift();

        foreach ($requiredSchemas as $schema) {
            $permutations = $permutations->crossJoin($schema->options);
        }

        foreach ($permutations as $permutation) {
            $this->checkProductPermutation(
                $quantityStep,
                $items,
                $product->items,
                $permutation,
            );
        }

        return [
            'available' => $available,
            'quantity' => $quantity,
            'shipping_time' => $shipping_time,
            'shipping_date' => $shipping_date,
            'productAvailabilities' => $productAvailabilities,
        ];
    }

    public function checkProductPermutation(
        float $quantityStep,
        Collection $items,
        Collection $requiredItems,
        ?Collection $selectedOptions = null,
    ): array {
        $quantity = 0;
        $shipping_time = null;
        $shipping_date = null;
        $productAvailabilities = [];

        if ($selectedOptions !== null && $selectedOptions->isNotEmpty()) {
            foreach ($selectedOptions as $option) {
                foreach ($option->items as $item) {
                    $requiredItems->push($item);
                }
            }
        }

        /** @var Item $requiredItem */
        foreach ($requiredItems as $requiredItem) {
            $item = $items->firstWhere('id', $requiredItem->getKey());

            if ($requiredItem->pivot->required_quantity > $item->quantity) {
                return $this->returnProductAvailability(false, 0);
            }

            $itemQuantity = floor(($item->quantity / $requiredItem->pivot->required_quantity) / $quantityStep) * $quantityStep;
            $item->quantity -= $requiredItem->pivot->required_quantity;

            $quantity = $quantity < $itemQuantity ? $itemQuantity : $quantity;

            $shipping_time = $item->shipping_time;
            $shipping_date = $item->shipping_date;
        }

        return $this->returnProductAvailability(
            true,
            $quantity,
            $shipping_time,
            $shipping_date,
            $productAvailabilities,
        );
    }

    public function isProductAvailable(Product $product): bool
    {
        $flagPermutations = false;
        /** @var Collection<int,mixed> $requiredSelectSchemas */
        $requiredSelectSchemas = $product->requiredSchemas->where('type.value', SchemaType::SELECT);
        if (!$requiredSelectSchemas->isEmpty() &&
            $requiredSelectSchemas->some(fn (Schema $schema) => $schema->options->count() > 0)
        ) {
            $flagPermutations = true;
            $items = [];
            foreach ($product->items as $productItem) {
                $items[$productItem->getKey()] = $productItem->pivot->required_quantity;
            }
            $hasAvailablePermutations = $this->checkPermutations($requiredSelectSchemas, $items);

            if ($hasAvailablePermutations) {
                return true;
            }
        }
        if (!$flagPermutations && $product->items->every(
            fn (Item $item) => $item->pivot->required_quantity <= $item->quantity ||
                !is_null($item->unlimited_stock_shipping_time) ||
                (!is_null($item->unlimited_stock_shipping_date) &&
                    $item->unlimited_stock_shipping_date >= Carbon::now())
        )) {
            return true;
        }

        return false;
    }

    public function checkPermutations(Collection $schemas, array $items): bool
    {
        $max = $schemas->count();
        $options = new Collection();

        return $this->getSchemaOptions($schemas->first(), $schemas, $options, $max, $items);
    }

    public function getSchemaOptions(
        Schema $schema,
        Collection $schemas,
        Collection $options,
        int $max,
        array $items,
        int $index = 0
    ): bool {
        foreach ($schema->options as $option) {
            $options->put($schema->getKey(), $option);
            if ($index < $max - 1) {
                $newIndex = $index + 1;

                return $this->getSchemaOptions($schemas->get($newIndex), $schemas, $options, $max, $items, $newIndex);
            }
            if ($index === $max - 1) {
                if ($this->isOptionsItemsAvailable($options, $items)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Collection $options
     * @param array<string, int> $items <unique id of item, quantity>
     *
     * @return bool
     */
    public function isOptionsItemsAvailable(Collection $options, array $items): bool
    {
        $itemsOptions = $options->pluck('items')->flatten()->groupBy('id');

        return $itemsOptions->every(
            function (Collection $item) use ($itemsOptions, $items) {
                $requiredAmount = ($items[$item->first()->getKey()] ?? 0) +
                    $itemsOptions->get($item->first()->getKey())->count();
                return $item->first()->quantity >= $requiredAmount;
            }
        );
    }

    private function returnProductAvailability(
        bool $available = false,
        ?float $quantity = null,
        ?int $shipping_time = null,
        ?string $shipping_date = null,
        array $productAvailabilities = [],
    ): array {
        return [
            'available' => $available,
            'quantity' => $quantity,
            'shipping_time' => $shipping_time,
            'shipping_date' => $shipping_date,
            'productAvailabilities' => $productAvailabilities,
        ];
    }

    /**
     * Get only required schemas of type SELECT and with related items
     */
    private function getRequiredSchemasWithItems(Product $product): Collection
    {
        return $product
            ->requiredSchemas()
            ->where('type', SchemaType::SELECT)
            ->whereHas('options', fn (Builder $query) => $query->whereHas('items'))
            ->with('options.items')
            ->get();
    }

    private function getAllRequiredItems(Product $product, Collection $requiredSchemas): Collection
    {
        $items = $product->items()->with('groupedDeposits')->get();;

        foreach ($requiredSchemas as $schema) {
            foreach ($schema->options as $option) {
                $items->merge($option->items()->with('groupedDeposits')->get());
            }
        }

        $items->unique();

        return $items;
    }

    private function calculateProductDeposits(
        Collection $items,
        Collection $timeDeposits,
        Product $product,
        string $type,
        array $requiredQuantities,
    ): mixed {
        $quantity = 0;

        $overstockedItems = [];
        foreach ($items as $item) {
            $overstockedItems[$item->getKey()] = 0.0;
        }

        /**
         * @var string $period
         * @var Collection $timeDeposit
         */
        foreach ($timeDeposits as $period => $timeDeposit) {
            $onlyOverstocked = [];
            if ($timeDeposit->count() < $items->count()) {
                $itemsWithDeposit = $timeDeposit->pluck('item_id')->toArray();

                $onlyOverstocked = Arr::where($overstockedItems, function ($value, $key) use ($itemsWithDeposit): bool {
                    return !in_array($key, $itemsWithDeposit);
                });
            }

            $quantities = $this->itemsMinQuantity(
                $timeDeposit,
                $overstockedItems,
                $requiredQuantities,
                $onlyOverstocked,
            );

            $minQuantity = $quantities->sort()->first();

            if ($minQuantity === 0.0) {
                foreach ($timeDeposit as $deposit) {
                    $overstockedItems[$deposit->item_id] += $deposit->quantity;
                }
                continue;
            }

            $overstockedItems = $this->addProductAvailability(
                $minQuantity,
                $product,
                $type,
                $period,
                $timeDeposit,
                $overstockedItems,
                $requiredQuantities,
            );
            $quantity += $minQuantity;
        }

        return $quantity;
    }

    private function addProductAvailability(
        mixed $minQuantity,
        Product $product,
        string $type,
        string $period,
        Collection $timeDeposit,
        array $overstockedItems,
        array $requiredQuantities,
    ): array {
        foreach ($timeDeposit as  $deposit) {
            $overstockedItems[$deposit->item_id] +=
                $deposit->quantity - ($minQuantity * $requiredQuantities[$deposit->item_id]);
        }

        $product->productAvailabilities()->create([
            'quantity' => $minQuantity,
            $type => $period,
        ]);

        return $overstockedItems;
    }

    private function productRequiredItems(Product $product): array
    {
        $items = Collection::make($product->items()->with('groupedDeposits')->get());

        $requiredItems = [];

        foreach ($items as $item) {
            $requiredItems[$item->getKey()] = $item->pivot->required_quantity;
        }

        $requiredQuantities = $product->requiredSchemas->where('type.value', SchemaType::SELECT);

        if (!$requiredQuantities->isEmpty()) {
            /** @var Schema $requiredQuantity */
            foreach ($requiredQuantities as $requiredQuantity) {
                $itemsOptions = $requiredQuantity->options->pluck('items')->flatten();
                foreach ($itemsOptions as $id => $item) {
                    if (!array_key_exists($id, $requiredItems)) {
                        $items->push($item->first());
                    }
                    $requiredItems[$id] = ($requiredItems[$id] ?? 0) + $item->pivot->reqired_quantity;
                }
            }
        }

        return [$items, $requiredItems];
    }

    private function itemsGroupedDeposits(Collection $items): array
    {
        $groupedDeposits = $items->pluck('groupedDeposits')->flatten(1);

        $shippingTimeDeposits = $groupedDeposits->filter(
            fn ($groupedDeposit): bool => $groupedDeposit->shipping_time !== null
        )->sortBy('shipping_time')->groupBy('shipping_time');

        $shippingDateDeposits = $groupedDeposits->filter(
            fn ($groupDeposit): bool => $groupDeposit->shipping_date !== null
        )->sortBy('shipping_date')->groupBy('shipping_date');

        return [$shippingTimeDeposits, $shippingDateDeposits];
    }

    private function itemsMinQuantity(
        Collection $timeDeposit,
        array $overstockedItems,
        array $requiredQuantities,
        array $onlyOverstocked = [],
    ): Collection {
        $quantities = Collection::make();

        foreach ($timeDeposit as  $deposit) {
            $quantities
                ->push(
                    floor(
                        ($deposit->quantity + $overstockedItems[$deposit->item_id])
                        / $requiredQuantities[$deposit->item_id]
                    )
                );
        }

        foreach ($onlyOverstocked as $key => $value) {
            $quantities->push(floor($value / $requiredQuantities[$key]));
        }

        return $quantities;
    }

    private function compareShippingDate(
        \Carbon\Carbon|null $shippingDate1,
        \Carbon\Carbon|null $shippingDate2,
        bool $isAfter = false,
    ): \Carbon\Carbon|null {
        if ($shippingDate1 === null || ($shippingDate1 instanceof Carbon &&
                ((!$isAfter && $shippingDate1->isBefore($shippingDate2)) ||
                    ($isAfter && $shippingDate1->isAfter($shippingDate2))))) {
            return $shippingDate2;
        }

        return $shippingDate1;
    }
}
