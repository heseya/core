<?php

namespace App\Services;

use App\Enums\SchemaType;
use App\Events\ProductUpdated;
use App\Exceptions\ServerException;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DepositServiceContract;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService implements AvailabilityServiceContract
{
    public function __construct(
        protected DepositServiceContract $depositService,
    ) {}

    /**
     * Function used to calculate "available" field for Options, Schemas and Products related to Item step by step
     * used after changing Item's quantity, usually after including Item in Order or creating Deposit
     * containing this item.
     */
    public function calculateItemAvailability(Item $item): void
    {
        $item->update($this->getCalculateItemDatesAvailability($item));

        // Options
        $options = $item->options;
        $options->each(fn (Option $option) => $this->calculateOptionAvailability($option));

        // Schemas
        $schemas = Schema::query()
            ->where('type', SchemaType::SELECT->value)
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
        $product->update($this->getCalculateProductAvailability($product));

        if ($product->wasChanged()) {
            ProductUpdated::dispatch($product);
        }
    }

    /**
     * @return array{quantity: float, shipping_time: (int|null), shipping_date: (\Carbon\Carbon|null)}
     */
    public function getCalculateItemDatesAvailability(Item $item): array
    {
        $dateTime = $this->depositService->getShippingTimeDateForQuantity($item);

        return [
            'quantity' => $item->quantity_real,
            'shipping_time' => $dateTime['shipping_time'],
            'shipping_date' => $dateTime['shipping_date'],
        ];
    }

    /**
     * @return array{available: bool, shipping_time: (int|null), shipping_date: (\Carbon\Carbon|null)}
     */
    public function getCalculateOptionAvailability(Option $option): array
    {
        // If option don't have any related items is always avaiable
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
            'available' => true,
            'shipping_time' => $shipping_time,
            'shipping_date' => $shipping_date,
        ];
    }

    /**
     * @return array{available: bool, shipping_time: (int|null), shipping_date: (\Carbon\Carbon|null)}
     */
    public function getCalculateSchemaAvailability(Schema $schema): array
    {
        if ($schema->type !== SchemaType::SELECT) {
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
        $requiredSchemasCount = $requiredSchemas->count();

        // simple return when no required schemas and items
        if ($requiredSchemasCount === 0 && $product->items->isEmpty()) {
            return $this->returnProductAvailability(true);
        }

        $requiredItems = $product->items;
        $items = $this->getAllRequiredItems($product, $requiredSchemas);

        // check only permutation when product don't have required schemas
        if ($requiredSchemasCount === 0) {
            return $this->checkProductPermutation($quantityStep, $items, $requiredItems);
        }

        $permutations = $requiredSchemas->first()->options;
        $requiredSchemas->shift();

        $permutations = $permutations->crossJoin(...$requiredSchemas->pluck('options'));

        if ($permutations->count() <= 0) {
            return $this->returnProductAvailability(true);
        }

        $available = false;
        $quantity = 0.0;
        $shipping_time = null;
        $shipping_date = null;

        foreach ($permutations as $permutation) {
            $permutationResult = $this->checkProductPermutation(
                $quantityStep,
                $items,
                $requiredItems,
                $permutation instanceof Option ? [$permutation] : $permutation,
            );

            $quantity = max($quantity, $permutationResult['quantity']);

            if ($permutationResult['available'] === true) {
                $available = true;
            }

            $shipping_time = $shipping_time === null ?
                $permutationResult['shipping_time'] : min($permutationResult['shipping_time'], $shipping_time);
            $shipping_date = $this->compareShippingDate(
                $shipping_date,
                $permutationResult['shipping_date'],
                true,
            );
        }

        // return if all permutations all unavailable
        if ($available === false) {
            return $this->returnProductAvailability(false, 0.0);
        }

        return $this->returnProductAvailability(
            true,
            $quantity,
            $shipping_time,
            $shipping_date,
        );
    }

    /**
     * Check single product permutation.
     */
    public function checkProductPermutation(
        float $quantityStep,
        Collection $items,
        Collection $requiredItems,
        ?array $selectedOptions = null,
    ): array {
        if ($selectedOptions !== null) {
            foreach ($selectedOptions as $option) {
                foreach ($option->items as $item) {
                    $requiredItems->push($item);
                }
            }
        }

        if ($requiredItems->count() <= 0) {
            return $this->returnProductAvailability(true);
        }

        $quantity = 0.0;
        $shipping_time = null;
        $shipping_date = null;
        $usedItems = [];

        /** @var Item $requiredItem */
        foreach ($requiredItems as $requiredItem) {
            $item = $items->firstWhere('id', $requiredItem->getKey());

            if (!($item instanceof Item)) {
                throw new ServerException('Not found item with id ' . $requiredItem->getKey());
            }

            // check if item is used again in same permutation
            $requiredQuantity = array_key_exists($item->getKey(), $usedItems) ?
                $usedItems[$item->getKey()] + $requiredItem->pivot->required_quantity :
                $requiredItem->pivot->required_quantity;

            if ($requiredQuantity > $item->quantity_real) {
                if (
                    $item->unlimited_stock_shipping_time === null
                    && $item->unlimited_stock_shipping_date === null
                ) {
                    return $this->returnProductAvailability(false, 0.0);
                }

                // set null only if quantity is 0
                $quantity = $quantity === 0.0 ? null : $quantity;
                $shipping_time = max($item->unlimited_stock_shipping_time, $shipping_time);
                $shipping_date = $this->compareShippingDate(
                    $item->unlimited_stock_shipping_date,
                    $shipping_date,
                );

                continue;
            }

            // save used item in permutation
            $usedItems[$item->getKey()] = $requiredItem->pivot->required_quantity;

            // round product quantity to product qty step
            if ($requiredQuantity === 0) {
                throw new Exception('Item with id ' . $item->getKey() . 'doesn\'t have required quantity');
            }

            $itemQuantity = floor($item->quantity_real / $requiredQuantity / $quantityStep) * $quantityStep;

            // override default 0 when got any result
            if ($quantity <= 0.0 || $itemQuantity < $quantity) {
                $quantity = $itemQuantity;
            }

            /** @var Collection<Deposit> $groupedDeposits */
            $groupedDeposits = $item->groupedDeposits;
            $sortedDeposits = $groupedDeposits->sort(static function (Deposit $a, Deposit $b) {
                $sortByTime = $a->shipping_time <=> $b->shipping_time;
                $sortByDate = $a->shipping_date <=> $b->shipping_date;

                return $sortByDate === 0 ? $sortByTime : $sortByDate;
            });

            foreach ($sortedDeposits as $deposit) {
                if ($requiredQuantity > 0.0 && $deposit->quantity >= $requiredQuantity) {
                    $shipping_time = max($deposit->shipping_time, $shipping_time);
                    $shipping_date = $this->compareShippingDate($deposit->shipping_date, $shipping_date);
                }

                $requiredQuantity -= $deposit->quantity;
            }

            $shipping_time ??= $item->unlimited_stock_shipping_time;
            $shipping_date ??= $item->unlimited_stock_shipping_date;
        }

        return $this->returnProductAvailability(
            true,
            $quantity,
            $shipping_time,
            $shipping_date,
        );
    }

    /**
     * Helper method for always generating same return array.
     */
    private function returnProductAvailability(
        bool $available = false,
        ?float $quantity = null,
        ?int $shipping_time = null,
        ?string $shipping_date = null,
    ): array {
        return [
            'available' => $available,
            'quantity' => $quantity,
            'shipping_time' => $shipping_time,
            'shipping_date' => $shipping_date,
        ];
    }

    /**
     * Get only required schemas of type SELECT and with related items.
     */
    private function getRequiredSchemasWithItems(Product $product): Collection
    {
        return $product
            ->requiredSchemas()
            ->where('type', SchemaType::SELECT->value)
            ->whereHas('options', fn (Builder $query) => $query->whereHas('items'))
            ->with('options.items')
            ->get();
    }

    /**
     * Get all items required by products required items and required schemas.
     */
    private function getAllRequiredItems(Product $product, Collection $requiredSchemas): Collection
    {
        $items = $product->items()->with('groupedDeposits')->get();

        foreach ($requiredSchemas as $schema) {
            foreach ($schema->options as $option) {
                foreach ($option->items()->with('groupedDeposits')->get() as $item) {
                    if ($items->where('id', $item->getKey())->count() <= 0) {
                        $items->push($item);
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Compares whether $shippingDate1 is before $shippingDate2.
     */
    private function compareShippingDate(
        \Carbon\Carbon|string|null $shippingDate1,
        \Carbon\Carbon|string|null $shippingDate2,
        bool $isAfter = false,
    ): \Carbon\Carbon|null {
        // TODO: find why this is string and remove this section
        if (is_string($shippingDate1)) {
            $shippingDate1 = Carbon::createFromTimeString($shippingDate1);
        }
        if (is_string($shippingDate2)) {
            $shippingDate2 = Carbon::createFromTimeString($shippingDate2);
        }

        if ($shippingDate1 === null || ($shippingDate1 instanceof Carbon
            && ((!$isAfter && $shippingDate1->isBefore($shippingDate2))
                || ($isAfter && $shippingDate1->isAfter($shippingDate2))))) {
            return $shippingDate2;
        }

        return $shippingDate1;
    }
}
