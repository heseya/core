<?php

namespace App\Services;

use App\Dtos\CartDto;
use App\Dtos\CartItemDto;
use App\Enums\SchemaType;
use App\Models\Item;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DepositServiceContract;
use App\Services\Contracts\ShippingTimeDateServiceContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ShippingTimeDateService implements ShippingTimeDateServiceContract
{
    public function __construct(
        private AvailabilityServiceContract $availabilityService,
        private DepositServiceContract $depositService
    ) {
    }

    public function stopShippingUnlimitedStockDate(): void
    {
        $items = $this->getItemsWithUnlimitedStockDateLast24h();
        $items->each(fn ($item) => $this->availabilityService->calculateAvailabilityOnOrderAndRestock($item));
    }

    public function getItemsWithUnlimitedStockDateLast24h(): Collection
    {
        $between = [Carbon::now()->addDays(-1)->toDateTimeString(), Carbon::now()->toDateTimeString()];

        return Item::query()
            ->whereBetween('unlimited_stock_shipping_date', $between)
            ->get();
    }

    public function getTimeAndDateForCart(CartDto $cart, Collection $products): array
    {
        $cartItemsAndQuantity = [];
        foreach ($products as $product) {
            /** @var CartItemDto $cartItem */
            $cartItem = Arr::first($cart->getItems(), function ($value, $key) use ($product) {
                return $value->getProductId() === $product->getKey();
            });

            $items = $product->items;
            foreach ($items as $item) {
                $quantity = $item->pivot->required_quantity * $cartItem->getQuantity();
                if (!isset($cartItemsAndQuantity[$item->getKey()])) {
                    $cartItemsAndQuantity[$item->getKey()] = ['item' => $item,'quantity' => $quantity];
                } else {
                    $cartItemsAndQuantity[$item->getKey()]['quantity'] += $quantity;
                }
            }
            $schemas = $cartItem->getSchemas();
            foreach ($cartItem->getSchemas() as $schemaId => $value) {
                $schema = $product->schemas()->findOrFail($schemaId);
                $optionValue = $schemas[$schema->getKey()] ?? null;
                if ($optionValue === null || !$schema->type->is(SchemaType::SELECT)) {
                    continue;
                }
                $option = $schema->options()->find($optionValue);
                foreach ($option->items as $item) {
                    if (!isset($cartItemsAndQuantity[$item->getKey()])) {
                        $cartItemsAndQuantity[$item->getKey()] = [
                            'item' => $item,
                            'quantity' => $cartItem->getQuantity(),
                        ];
                    } else {
                        $cartItemsAndQuantity[$item->getKey()]['quantity'] += $cartItem->getQuantity();
                    }
                }
            }
        }

        return $this->depositService->getTimeAndDateForCartItems($cartItemsAndQuantity);
    }
}
