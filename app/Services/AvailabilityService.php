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
     *
     * @param Item $item
     *
     * @return void
     */
    public function calculateAvailabilityOnOrderAndRestock(Item $item): void
    {
        $options = $item->options;
        $options->each(fn ($option) => $this->calculateOptionAvailability($option));

        $schemas = Schema::query()
            ->where('type', SchemaType::SELECT)
            ->whereIn('id', $options->pluck('schema_id'))
            ->get();

        $schemas->each(fn ($schema) => $this->calculateSchemaAvailability($schema));
        $schemas
            ->pluck('products')
            ->flatten()
            ->unique('id')
            ->each(fn ($product) => $this->calculateProductAvailability($product));

        $item->products->each(fn ($product) => $this->calculateProductAvailability($product));
    }

    public function calculateOptionAvailability(Option $option): void
    {
        if ($option->items->count() <= 0) {
            $option->update([
                'available' => true,
                'shipping_time' => null,
                'shipping_date' => null,
            ]);

            return;
        }

        if ($option->available && $option->items->some(fn ($item) => $item->quantity <= 0 &&
            $item->unlimited_stock_shipping_time === null &&
            ($item->unlimited_stock_shipping_date === null ||
                $item->unlimited_stock_shipping_date < Carbon::now())
        )) {
            $option->update([
                'available' => false,
                'shipping_time' => null,
                'shipping_date' => null,
            ]);
        } elseif (!$option->available && $option->items->every(fn ($item) => $item->quantity > 0 ||
            $item->unlimited_stock_shipping_time !== null ||
            ($item->unlimited_stock_shipping_date !== null &&
                $item->unlimited_stock_shipping_date >= Carbon::now())
        )) {
            $option->update([
                'available' => true,
            ] + $this->depositService->getMaxShippingTimeDateForItems($option->items));
        }
    }

    public function calculateSchemaAvailability(Schema $schema): void
    {
        if (!$schema->required || !$schema->type->is(SchemaType::SELECT)) {
            $schema->available = true;
            $schema->shipping_time = null;
            $schema->shipping_date = null;
        }

        if (
            $schema->available &&
            $schema->options->every(fn ($option) => !$option->available)
        ) {
            $schema->available = false;
            $schema->shipping_time = null;
            $schema->shipping_date = null;
        } elseif (
            !$schema->available &&
            $schema->options->some(fn ($option) => $option->available)
        ) {
            $schema->available = true;
            $schema->fill($this->depositService->getMinShippingTimeDateForOptions($schema->options));
        }

        $schema->save();
    }

    public function calculateProductAvailability(Product $product): void
    {
        [$items, $requiredItems] = $this->productRequiredItems($product);

        [$shippingTimeDeposits, $shippingDateDeposits] = $this->itemsGroupedDeposits($items);

        $product->productAvailabilities()->delete();

        $quantity = $this->calculateProductDeposits(
            $items,
            $shippingTimeDeposits,
            $product,
            'shipping_time',
            $requiredItems,
        );
        $quantity += $this->calculateProductDeposits(
            $items,
            $shippingDateDeposits,
            $product,
            'shipping_date',
            $requiredItems,
        );

        $product->update([
            'quantity' => $quantity,
            'available' => $this->isProductAvailable($product),
        ] + $this->depositService->getProductShippingTimeDate($product));

        if ($product->wasChanged()) {
            ProductUpdated::dispatch($product);
        }
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

        $requiredSchemas = $product->requiredSchemas->where('type.value', SchemaType::SELECT);

        if (!$requiredSchemas->isEmpty()) {
            /** @var Schema $requiredSchema */
            foreach ($requiredSchemas as $requiredSchema) {
                $itemsOptions = $requiredSchema->options->pluck('items')->flatten()->groupBy('id');
                foreach ($itemsOptions as $id => $item) {
                    if (!array_key_exists($id, $requiredItems)) {
                        $items->push($item->first());
                    }
                    $requiredItems[$id] = ($requiredItems[$id] ?? 0) + $item->count();
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
}
