<?php

namespace App\Services;

use App\Dtos\CartDto;
use App\Dtos\CartItemDto;
use App\Dtos\CartLengthConditionDto;
use App\Dtos\CartOrderDto;
use App\Dtos\ConditionDto;
use App\Dtos\ConditionGroupDto;
use App\Dtos\CouponDto;
use App\Dtos\CouponIndexDto;
use App\Dtos\CouponsCountConditionDto;
use App\Dtos\DateBetweenConditionDto;
use App\Dtos\MaxUsesConditionDto;
use App\Dtos\MaxUsesPerUserConditionDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderProductDto;
use App\Dtos\OrderValueConditionDto;
use App\Dtos\ProductInConditionDto;
use App\Dtos\ProductInSetConditionDto;
use App\Dtos\SaleDto;
use App\Dtos\SaleIndexDto;
use App\Dtos\TimeBetweenConditionDto;
use App\Dtos\UserInConditionDto;
use App\Dtos\UserInRoleConditionDto;
use App\Dtos\WeekDayInConditionDto;
use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Events\CouponCreated;
use App\Events\CouponDeleted;
use App\Events\CouponUpdated;
use App\Events\SaleCreated;
use App\Events\SaleDeleted;
use App\Events\SaleUpdated;
use App\Exceptions\ClientException;
use App\Exceptions\StoreException;
use App\Jobs\CalculateDiscount;
use App\Models\App;
use App\Models\CartItemResponse;
use App\Models\CartResource;
use App\Models\ConditionGroup;
use App\Models\CouponShortResource;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesShortResource;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\SettingsServiceContract;
use Carbon\Carbon;
use Heseya\Dto\Missing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class DiscountService implements DiscountServiceContract
{
    public function __construct(
        private SettingsServiceContract $settingsService,
        private MetadataServiceContract $metadataService
    ) {
    }

    public function calc(float $value, Discount $discount): float
    {
        if (isset($discount->pivot) && $discount->pivot->type !== null) {
            $discount->type = $discount->pivot->type;
            $discount->value = $discount->pivot->value;
        }

        if ($discount->type->is(DiscountType::PERCENTAGE)) {
            return $value * $discount->value / 100;
        }

        if ($discount->type->is(DiscountType::AMOUNT)) {
            return $discount->value;
        }

        throw new ClientException(Exceptions::CLIENT_DISCOUNT_TYPE_NOT_SUPPORTED, errorArray: [
            'type' => $discount->type,
        ]);
    }

    public function index(SaleIndexDto|CouponIndexDto $dto): LengthAwarePaginator
    {
        return Discount::searchByCriteria($dto->toArray())
            ->orderBy('updated_at', 'DESC')
            ->with(['orders', 'products', 'productSets', 'conditionGroups', 'shippingMethods', 'metadata'])
            ->paginate(Config::get('pagination.per_page'));
    }

    public function store(SaleDto|CouponDto $dto): Discount
    {
        $discount = Discount::create($dto->toArray());

        $discount->products()->attach($dto->getTargetProducts());
        $discount->productSets()->attach($dto->getTargetSets());
        $discount->shippingMethods()->attach($dto->getTargetShippingMethods());

        $conditionGroup = $dto->getConditionGroups();
        if (!$conditionGroup instanceof Missing && count($conditionGroup) > 0) {
            $discount->conditionGroups()->attach($this->createConditionGroupsToAttach($conditionGroup));
        }

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($discount, $dto->getMetadata());
        }

        if ($dto instanceof CouponDto) {
            CouponCreated::dispatch($discount);
        } else {
            CalculateDiscount::dispatchIf(
                $discount->target_type->is(DiscountTargetType::PRODUCTS),
                $discount,
            );
            SaleCreated::dispatch($discount);
        }

        return $discount;
    }

    public function update(Discount $discount, SaleDto|CouponDto $dto): Discount
    {
        $discount->update($dto->toArray());

        if (!$dto->getTargetProducts() instanceof Missing) {
            $discount->products()->sync($dto->getTargetProducts());
        }

        if (!$dto->getTargetSets() instanceof Missing) {
            $discount->productSets()->sync($dto->getTargetSets());
        }

        if (!$dto->getTargetShippingMethods() instanceof Missing) {
            $discount->shippingMethods()->sync($dto->getTargetShippingMethods());
        }

        $conditionGroup = $dto->getConditionGroups();
        if (!$conditionGroup instanceof Missing) {
            $discount->conditionGroups()->delete();
            if (count($conditionGroup) > 0) {
                $discount->conditionGroups()->attach($this->createConditionGroupsToAttach($conditionGroup));
            }
        }

        if ($dto instanceof CouponDto) {
            CouponUpdated::dispatch($discount);
        } else {
            CalculateDiscount::dispatch($discount, true);
            SaleUpdated::dispatch($discount);
        }

        return $discount;
    }

    public function destroy(Discount $discount): void
    {
        if ($discount->delete()) {
            if ($discount->code !== null) {
                CouponDeleted::dispatch($discount);
            } else {
                CalculateDiscount::dispatchIf(
                    $discount->target_type->is(DiscountTargetType::PRODUCTS),
                    $discount,
                );
                SaleDeleted::dispatch($discount);
            }
        }
    }

    public function checkCondition(
        DiscountCondition $condition,
        ?CartOrderDto $dto = null,
        ?float $cartValue = 0,
    ): bool {
        return match ($condition->type->value) {
            ConditionType::ORDER_VALUE => $this->checkConditionOrderValue($condition, $cartValue),
            ConditionType::USER_IN_ROLE => $this->checkConditionUserInRole($condition),
            ConditionType::USER_IN => $this->checkConditionUserIn($condition),
            ConditionType::PRODUCT_IN_SET => $this->checkConditionProductInSet($condition, $dto->getProductIds()),
            ConditionType::PRODUCT_IN => $this->checkConditionProductIn($condition, $dto->getProductIds()),
            ConditionType::DATE_BETWEEN => $this->checkConditionDateBetween($condition),
            ConditionType::TIME_BETWEEN => $this->checkConditionTimeBetween($condition),
            ConditionType::MAX_USES => $this->checkConditionMaxUses($condition),
            ConditionType::MAX_USES_PER_USER => $this->checkConditionMaxUsesPerUser($condition),
            ConditionType::WEEKDAY_IN => $this->checkConditionWeekdayIn($condition),
            ConditionType::CART_LENGTH => $this->checkConditionCartLength($condition, $dto->getCartLength()),
            ConditionType::COUPONS_COUNT => $this->checkConditionCouponsCount($condition, count($dto->getCoupons())),
            default => false,
        };
    }

    public function checkConditionGroup(
        ConditionGroup $group,
        CartOrderDto $dto,
        float $cartValue,
    ): bool {
        foreach ($group->conditions as $condition) {
            if (!$this->checkCondition($condition, $dto, $cartValue)) {
                return false;
            }
        }
        return true;
    }

    public function checkConditionGroups(
        Discount $discount,
        CartOrderDto $dto,
        float $cartValue,
    ): bool {
        if (count($discount->conditionGroups) > 0) {
            foreach ($discount->conditionGroups as $conditionGroup) {
                if ($this->checkConditionGroup($conditionGroup, $dto, $cartValue)) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    public function calcOrderDiscounts(Order $order, OrderDto $orderDto): Order
    {
        $coupons = $orderDto->getCoupons() instanceof Missing ? [] : $orderDto->getCoupons();
        $sales = $orderDto->getSaleIds() instanceof Missing ? [] : $orderDto->getSaleIds();
        $discounts = $this->getSalesAndCoupons($coupons);

        /** @var Discount $discount */
        foreach ($discounts as $discount) {
            if ($this->checkConditionGroups($discount, $orderDto, $order->cart_total)) {
                $order = $this->applyDiscountOnOrder($discount, $order);
            } elseif (
                ($discount->code === null && in_array($discount->getKey(), $sales)) ||
                $discount->code !== null
            ) {
                [$type, $id] = $discount->code !== null ? ['coupon', $discount->code] : ['sale', $discount->getKey()];
                throw new ClientException(Exceptions::CLIENT_CANNOT_APPLY_SELECTED_DISCOUNT_TYPE, errorArray: [
                    'type' => $type,
                    'id' => $id,
                ]);
            }
        }

        $order->cart_total = round($order->cart_total, 2);
        $order->shipping_price = round($order->shipping_price, 2);
        $order->summary = $order->cart_total + $order->shipping_price;
        $order->paid = $order->summary <= 0;

        return $order;
    }

    public function calcCartDiscounts(CartDto $cart, Collection $products): CartResource
    {
        $discounts = $this->getSalesAndCoupons($cart->getCoupons());
        $shippingMethod = null;

        if (!$cart->getShippingMethodId() instanceof Missing) {
            $shippingMethod = ShippingMethod::findOrFail($cart->getShippingMethodId());
        }

        // Obliczanie wartości początkowej koszyka
        $cartItems = [];
        $cartValue = 0;

        foreach ($products as $product) {
            /** @var CartItemDto $cartItem */
            $cartItem = Arr::first($cart->getItems(), function ($value, $key) use ($product) {
                return $value->getProductId() === $product->getKey();
            });

            $price = $product->price;

            foreach ($cartItem->getSchemas() as $schemaId => $value) {
                $schema = $product->schemas()->findOrFail($schemaId);

                $price += $schema->getPrice($value, $cartItem->getSchemas());
            }
            $cartValue += $price * $cartItem->getQuantity();
            array_push(
                $cartItems,
                new CartItemResponse($cartItem->getCartitemId(), $price, $price, $cartItem->getQuantity()),
            );
        }

        $shippingPrice = $shippingMethod !== null ? $shippingMethod->getPrice($cartValue) : 0;
        $summary = $cartValue + $shippingPrice;

        $cartResource = new CartResource(
            Collection::make($cartItems),
            Collection::make(),
            Collection::make(),
            $cartValue,
            $cartValue,
            $shippingPrice,
            $shippingPrice,
            $summary,
        );

        if ($cartResource->items->isEmpty()) {
            return $cartResource;
        }

        foreach ($discounts as $discount) {
            if ($this->checkConditionGroups($discount, $cart, $cartResource->cart_total)) {
                $cartResource = $this->applyDiscountOnCart($discount, $cart, $cartResource);
                $newSummary = $cartResource->cart_total + $cartResource->shipping_price;
                $appliedDiscount = $summary - $newSummary;

                if ($discount->code !== null) {
                    $cartResource->coupons->push(
                        new CouponShortResource($discount->getKey(), $discount->name, $appliedDiscount, $discount->code)
                    );
                } else {
                    $cartResource->sales->push(
                        new SalesShortResource($discount->getKey(), $discount->name, $appliedDiscount)
                    );
                }

                $summary = $newSummary;
            }
        }

        $cartResource->summary = $cartResource->cart_total + $cartResource->shipping_price;
        return $cartResource;
    }

    public function applyDiscountOnProduct(
        Product $product,
        OrderProductDto $orderProductDto,
        Discount $discount
    ): OrderProduct {
        $price = $product->price;

        foreach ($orderProductDto->getSchemas() as $schemaId => $value) {
            $schema = $product->schemas()->findOrFail($schemaId);

            $price += $schema->getPrice($value, $orderProductDto->getSchemas());
        }

        return new OrderProduct([
            'product_id' => $product->getKey(),
            'quantity' => $orderProductDto->getQuantity(),
            'price' => $this->calcPrice($price, $product->getKey(), $discount),
        ]);
    }

    public function applyDiscountsOnProducts(Collection $products): void
    {
        foreach ($products as $product) {
            $this->applyDiscountsOnProduct($product);
        }
    }

    public function applyDiscountsOnProduct(Product $product): void
    {
        $sales = Discount::where('code', null)
            ->where('target_type', DiscountTargetType::PRODUCTS)
            ->where('target_is_allow_list', true)
            ->whereHas('products', function ($query) use ($product): void {
                $query->where('id', $product->getKey());
            })
            ->orWhere(function ($query) use ($product): void {
                $query->where('code', null)
                    ->where('target_type', DiscountTargetType::PRODUCTS)
                    ->where('target_is_allow_list', true)
                    ->whereHas('productSets', function ($query) use ($product): void {
                        $query->whereHas('products', function ($query) use ($product): void {
                            $query->where('id', $product->getKey());
                        });
                    });
            })
            ->orWhere(function ($query) use ($product): void {
                $query->where('code', null)
                    ->where('target_type', DiscountTargetType::PRODUCTS)
                    ->where('target_is_allow_list', false)
                    ->whereDoesntHave('products', function ($query) use ($product): void {
                        $query->where('id', $product->getKey());
                    })
                    ->whereDoesntHave('productSets', function ($query) use ($product): void {
                        $query->whereHas('products', function ($query) use ($product): void {
                            $query->where('id', $product->getKey());
                        });
                    });
            })
            ->with(['products', 'productSets', 'conditionGroups', 'shippingMethods'])
            ->get();

        $sales = $this->sortDiscounts($sales);

        $minPriceDiscounted = $product->price_min_initial;
        $maxPriceDiscounted = $product->price_max_initial;

        $minimalProductPrice = $this->settingsService->getMinimalPrice('minimal_product_price');

        $productSales = Collection::make();

        foreach ($sales as $sale) {
            if ($this->checkConditionGroupsForProduct($sale)) {
                if ($minPriceDiscounted !== $minimalProductPrice) {
                    $minPriceDiscounted = $this
                        ->calcProductPriceDiscount($sale, $minPriceDiscounted, $minimalProductPrice);
                }

                if ($maxPriceDiscounted !== $minimalProductPrice) {
                    $maxPriceDiscounted = $this
                        ->calcProductPriceDiscount($sale, $maxPriceDiscounted, $minimalProductPrice);
                }

                $productSales->push($sale);
            }
        }

        $product->update([
            'price_min' => $minPriceDiscounted,
            'price_max' => $maxPriceDiscounted,
        ]);
        $product->sales()->sync($productSales->pluck('id'));
        $product->searchable();
    }

    public function applyDiscountOnOrderProduct(OrderProduct $orderProduct, Discount $discount): OrderProduct
    {
        $minimalProductPrice = $this->settingsService->getMinimalPrice('minimal_product_price');
        $price = $orderProduct->price;

        if (
            $price !== $minimalProductPrice
            && $this->checkIsProductInDiscount($orderProduct->product_id, $discount)
        ) {
            $this->calcOrderProductDiscount($orderProduct, $discount);
        }

        return $orderProduct;
    }

    public function applyDiscountOnCartItem(
        Discount $discount,
        CartItemDto $cartItem,
        CartResource $cart,
    ): CartItemResponse {
        /** @var CartItemResponse $result */
        $result = $cart->items->filter(
            fn ($value) => $value->cartitem_id === $cartItem->getCartitemId(),
        )->first();

        $result->price_discounted = $this->calcPrice(
            $result->price_discounted,
            $cartItem->getProductId(),
            $discount,
        );

        return $result;
    }

    public function applyDiscountOnOrder(Discount $discount, Order $order): Order
    {
        $refreshedOrder = $order->fresh();
        if (
            ($discount->target_type->value === DiscountTargetType::ORDER_VALUE
                || $discount->target_type->value === DiscountTargetType::SHIPPING_PRICE)
            && $refreshedOrder->discounts->count() === 0) {
            $order = $this->roundProductPrices($order);
        }
        return match ($discount->target_type->value) {
            DiscountTargetType::PRODUCTS => $this->applyDiscountOnOrderProducts($order, $discount),
            DiscountTargetType::ORDER_VALUE => $this->applyDiscountOnOrderValue($order, $discount),
            DiscountTargetType::SHIPPING_PRICE => $this->applyDiscountOnOrderShipping($order, $discount),
            DiscountTargetType::CHEAPEST_PRODUCT => $this->applyDiscountOnOrderCheapestProduct($order, $discount),
            default => throw new StoreException('Unsupported discount target type'),
        };
    }

    public function calcAppliedDiscount(float $price, float $appliedDiscount, string $setting): float
    {
        $minimalPrice = $this->settingsService->getMinimalPrice($setting);

        return $price - $appliedDiscount < $minimalPrice ? $price - $minimalPrice : $appliedDiscount;
    }

    public function activeSales(): Collection
    {
        $sales = Discount::where('code', null)
            ->where('target_type', DiscountTargetType::PRODUCTS)
            ->whereHas('conditionGroups', function ($query): void {
                $query
                    ->whereHas('conditions', function ($query): void {
                        $query
                            ->where('type', ConditionType::DATE_BETWEEN)
                            ->orWhere('type', ConditionType::TIME_BETWEEN)
                            ->orWhere('type', ConditionType::WEEKDAY_IN);
                    });
            })
            ->with(['conditionGroups', 'conditionGroups.conditions'])
            ->get();

        return $sales->filter(function ($sale): bool {
            foreach ($sale->conditionGroups as $conditionGroup) {
                foreach ($conditionGroup->conditions as $condition) {
                    $result = match ($condition->type->value) {
                        ConditionType::DATE_BETWEEN => $this->checkConditionDateBetween($condition),
                        ConditionType::TIME_BETWEEN => $this->checkConditionTimeBetween($condition),
                        ConditionType::WEEKDAY_IN => $this->checkConditionWeekdayIn($condition),
                        default => false,
                    };
                    if ($result) {
                        return true;
                    }
                }
            }
            return false;
        });
    }

    public function checkDiscountHasTimeConditions(Discount $discount): bool
    {
        $conditionsGroups = $discount->conditionGroups;
        foreach ($conditionsGroups as $conditionGroup) {
            foreach ($conditionGroup->conditions as $condition) {
                if ($condition->type->in(
                    [
                        ConditionType::DATE_BETWEEN,
                        ConditionType::TIME_BETWEEN,
                        ConditionType::WEEKDAY_IN,
                    ]
                )) {
                    return true;
                }
            }
        }
        return false;
    }

    public function checkDiscountTimeConditions(Discount $discount): bool
    {
        foreach ($discount->conditionGroups as $conditionGroup) {
            foreach ($conditionGroup->conditions as $condition) {
                $result = match ($condition->type->value) {
                    ConditionType::DATE_BETWEEN => $this->checkConditionDateBetween($condition),
                    ConditionType::TIME_BETWEEN => $this->checkConditionTimeBetween($condition),
                    ConditionType::WEEKDAY_IN => $this->checkConditionWeekdayIn($condition),
                    default => false,
                };
                if ($result) {
                    return true;
                }
            }
        }
        return false;
    }

    private function roundProductPrices(Order $order): Order
    {
        $totalPrice = 0;
        foreach ($order->products as $product) {
            $product->price = round($product->price, 2);
            $totalPrice += $product->price * $product->quantity;
        }

        $order->cart_total = round($totalPrice, 2);

        return $order;
    }

    private function calcProductPriceDiscount(Discount $discount, float $price, float $minimalProductPrice): float
    {
        $price -= $this->calc($price, $discount);
        return $price < $minimalProductPrice
            ? $minimalProductPrice : $price;
    }

    private function checkConditionGroupForProduct(ConditionGroup $group): bool
    {
        foreach ($group->conditions as $condition) {
            if (!$this->checkConditionForProduct($condition)) {
                return false;
            }
        }
        return true;
    }

    private function checkConditionGroupsForProduct(Discount $discount): bool
    {
        if ($discount->conditionGroups->count() > 0) {
            foreach ($discount->conditionGroups as $conditionGroup) {
                if ($this->checkConditionGroupForProduct($conditionGroup)) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    private function checkConditionForProduct(DiscountCondition $condition): bool
    {
        return match ($condition->type->value) {
            ConditionType::USER_IN_ROLE => $this->checkConditionUserInRole($condition),
            ConditionType::USER_IN => $this->checkConditionUserIn($condition),
            ConditionType::DATE_BETWEEN => $this->checkConditionDateBetween($condition),
            ConditionType::TIME_BETWEEN => $this->checkConditionTimeBetween($condition),
            ConditionType::MAX_USES => $this->checkConditionMaxUses($condition),
            ConditionType::MAX_USES_PER_USER => $this->checkConditionMaxUsesPerUser($condition),
            ConditionType::WEEKDAY_IN => $this->checkConditionWeekdayIn($condition),
            default => false,
        };
    }

    private function calcOrderProductDiscount(
        OrderProduct $orderProduct,
        Discount $discount,
    ): void {
        $appliedDiscount = $this->calcAppliedDiscount(
            $orderProduct->price,
            $this->calc($orderProduct->price, $discount),
            'minimal_product_price',
        );
        $orderProduct->price -= $appliedDiscount;

        # Dodanie zniżki do orderProduct
        $this->attachDiscount($orderProduct, $discount, $appliedDiscount);
    }

    private function applyDiscountOnOrderValue(Order $order, Discount $discount): Order
    {
        $appliedDiscount = $this->calcAppliedDiscount(
            $order->cart_total,
            $this->calc($order->cart_total, $discount),
            'minimal_order_price',
        );

        $order->cart_total -= $appliedDiscount;

        $this->attachDiscount($order, $discount, $appliedDiscount);

        return $order;
    }

    private function applyDiscountOnOrderShipping(Order $order, Discount $discount): Order
    {
        if (
            in_array(
                $order->shipping_method_id,
                $discount->shippingMethods->pluck('id')->toArray()
            ) === $discount->target_is_allow_list
        ) {
            $appliedDiscount = $this->calcAppliedDiscount(
                $order->shipping_price,
                $this->calc($order->shipping_price, $discount),
                'minimal_shipping_price',
            );
            $order->shipping_price -= $appliedDiscount;

            $this->attachDiscount($order, $discount, $appliedDiscount);
        }

        return $order;
    }

    private function attachDiscount(Order|OrderProduct $object, Discount $discount, float $appliedDiscount): void
    {
        $code = $discount->code !== null ? ['code' => $discount->code] : [];

        $object->discounts()->attach(
            $discount->getKey(),
            [
                'name' => $discount->name,
                'type' => $discount->type,
                'value' => $discount->value,
                'target_type' => $discount->target_type,
                'applied_discount' => $appliedDiscount,
            ] + $code,
        );
    }

    private function applyDiscountOnOrderProducts(Order $order, Discount $discount): Order
    {
        $cartValue = 0;

        /** @var OrderProduct $product */
        foreach ($order->products as $product) {
            $product = $this->applyDiscountOnOrderProduct($product, $discount);
            $cartValue += $product->price * $product->quantity;
        }

        $order->cart_total = $cartValue;

        return $order;
    }

    private function applyDiscountOnOrderCheapestProduct(Order $order, Discount $discount): Order
    {
        $product = $order->products->sortBy([
            ['price', 'asc'],
            ['quantity', 'asc'],
        ])->first();

        if ($product->quantity > 1) {
            $product->update(['quantity' => $product->quantity - 1]);

            /** @var OrderProduct $newProduct */
            $newProduct = $order->products()->create([
                'product_id' => $product->product_id,
                'quantity' => 1,
                'price' => $product->price,
                'price_initial' => $product->price_initial,
                'name' => $product->name,
            ]);

            $product->discounts->each(function (Discount $discount) use ($newProduct): void {
                // @phpstan-ignore-next-line
                $this->attachDiscount($newProduct, $discount, $discount->pivot->applied_discount);
            });

            $product = $newProduct;
        }

        $minimalProductPrice = $this->settingsService->getMinimalPrice('minimal_product_price');
        $price = $product->price;

        if ($price !== $minimalProductPrice) {
            $this->calcOrderProductDiscount($product, $discount);
            $product->save();
        }

        $order->cart_total -= ($price - $product->price) * $product->quantity;

        return $order;
    }

    private function applyDiscountOnCart(Discount $discount, CartDto $cartDto, CartResource $cart): CartResource
    {
        return match ($discount->target_type->value) {
            DiscountTargetType::PRODUCTS => $this->applyDiscountOnCartItems($discount, $cartDto, $cart),
            DiscountTargetType::ORDER_VALUE => $this->applyDiscountOnCartTotal($discount, $cart),
            DiscountTargetType::SHIPPING_PRICE => $this->applyDiscountOnCartShipping($discount, $cartDto, $cart),
            DiscountTargetType::CHEAPEST_PRODUCT => $this->applyDiscountOnCartCheapestItem($discount, $cart),
            default => throw new StoreException('Unknown discount target type'),
        };
    }

    private function applyDiscountOnCartShipping(
        Discount $discount,
        CartDto $cartDto,
        CartResource $cartResource
    ): CartResource {
        if (
            in_array(
                $cartDto->getShippingMethodId(),
                $discount->shippingMethods->pluck('id')->toArray()
            ) === $discount->target_is_allow_list
        ) {
            $cartResource->shipping_price -= $this->calcAppliedDiscount(
                $cartResource->shipping_price,
                $this->calc($cartResource->shipping_price, $discount),
                'minimal_shipping_price',
            );
        }
        return $cartResource;
    }

    private function applyDiscountOnCartTotal(Discount $discount, CartResource $cartResource): CartResource
    {
        $cartResource->cart_total -= $this->calcAppliedDiscount(
            $cartResource->cart_total,
            $this->calc($cartResource->cart_total, $discount),
            'minimal_order_price',
        );
        return $cartResource;
    }

    private function applyDiscountOnCartItems(Discount $discount, CartDto $cartDto, CartResource $cart): CartResource
    {
        $cartItems = [];
        $cartValue = 0;

        /** @var CartItemDto $item */
        foreach ($cartDto->getItems() as $item) {
            $cartItem = $cart->items->filter(function ($value, $key) use ($item) {
                return $value->cartitem_id === $item->getCartitemId();
            })->first();

            if ($cartItem === null) {
                continue;
            }

            $cartItem = $this->applyDiscountOnCartItem($discount, $item, $cart);

            array_push($cartItems, $cartItem);

            $cartValue += $cartItem->price_discounted * $item->getQuantity();
        }

        $cart->items = Collection::make($cartItems);
        $cart->cart_total = $cartValue;

        return $cart;
    }

    private function applyDiscountOnCartCheapestItem(
        Discount $discount,
        CartResource $cart,
    ): CartResource {
        /** @var CartItemResponse $cartItem */
        $cartItem = $cart->items->sortBy([
            ['price_discounted', 'asc'],
            ['quantity', 'asc'],
        ])->first();

        if ($cartItem->quantity > 1) {
            $cart->items->first(function ($value) use ($cartItem): bool {
                return $value->cartitem_id === $cartItem->cartitem_id && $value->quantity === $cartItem->quantity;
            })->quantity = $cartItem->quantity - 1;

            $cartItem = new CartItemResponse(
                $cartItem->cartitem_id,
                $cartItem->price,
                $cartItem->price_discounted,
                1,
            );
            $cart->items->push($cartItem);
        }

        $price = $cartItem->price_discounted;

        $minimalProductPrice = $this->settingsService->getMinimalPrice('minimal_product_price');

        if ($price !== $minimalProductPrice) {
            $newPrice = $price - $this->calc($price, $discount);

            $cartItem->price_discounted = $newPrice < $minimalProductPrice ? $minimalProductPrice : $newPrice;

            $cart->cart_total -= ($price - $cartItem->price_discounted) * $cartItem->quantity;
        }

        return $cart;
    }

    private function getSalesAndCoupons(array|Missing $couponIds): Collection
    {
        // Dostępne promocje
        $sales = Discount::where('code', null)
            ->with(['orders', 'products', 'productSets', 'conditionGroups', 'conditionGroups.conditions'])->get();
        // Nie przesłano kuponów
        if ($couponIds instanceof Missing) {
            return $this->sortDiscounts($sales);
        }
        // Wybrane kupony
        $coupons = Discount::whereIn('code', $couponIds)
            ->with(['orders', 'products', 'productSets', 'conditionGroups', 'conditionGroups.conditions'])->get();

        // Posortowanie w kolejności do naliczania zniżek
        return $this->sortDiscounts($sales->merge($coupons));
    }

    private function sortDiscounts(Collection $discounts): Collection
    {
        // Sortowanie zniżek w kolejności naliczania (Target type ASC, Discount type ASC, Priority DESC)
        return $discounts->sortBy([
            fn ($a, $b) => DiscountTargetType::getPriority($a->target_type->value)
                <=> DiscountTargetType::getPriority($b->target_type->value),
            fn ($a, $b) => DiscountType::getPriority($a->type->value) <=> DiscountType::getPriority($b->type->value),
            fn ($a, $b) => $b->priority <=> $a->priority,
        ]);
    }

    private function calcPrice(float $price, string $productId, Discount $discount): float
    {
        $minimalProductPrice = $this->settingsService->getMinimalPrice('minimal_product_price');

        if ($price !== $minimalProductPrice && $this->checkIsProductInDiscount($productId, $discount)) {
            $price -= $this->calc($price, $discount);
            $price = $price < $minimalProductPrice ? $minimalProductPrice : $price;
        }

        return $price;
    }

    private function checkIsProductInDiscount(string $productId, Discount $discount): bool
    {
        $inDiscount = $this->checkIsProductInDiscountProducts($productId, $discount);

        if ($inDiscount !== $discount->target_is_allow_list) {
            return $this->checkIsProductInDiscountProductSets($productId, $discount);
        }

        return $inDiscount;
    }

    private function checkIsProductInDiscountProducts(string $productId, Discount $discount): bool
    {
        return in_array($productId, $discount->products->pluck('id')->all()) === $discount->target_is_allow_list;
    }

    private function checkIsProductInDiscountProductSets(string $productId, Discount $discount): bool
    {
        $product = Product::where('id', $productId)->firstOrFail();
        $productSets = $product->sets()->whereIn('id', $discount->productSets->pluck('id')->all())->count();

        if ($discount->target_is_allow_list && $productSets > 0) {
            return true;
        }

        if (!$discount->target_is_allow_list && $productSets === 0) {
            return true;
        }

        return false;
    }

    private function createConditionGroupsToAttach(array $conditions): array
    {
        $result = [];
        foreach ($conditions as $condition) {
            array_push($result, $this->createConditionGroup($condition));
        }
        return Collection::make($result)->pluck('id')->all();
    }

    private function createConditionGroup(ConditionGroupDto $dto): ConditionGroup
    {
        /** @var ConditionGroup $conditionGroup */
        $conditionGroup = ConditionGroup::create();

        /** @var ConditionDto $condition */
        foreach ($dto->getConditions() as $condition) {
            /** @var DiscountCondition $discountCondition */
            $discountCondition = $conditionGroup->conditions()->create([
                'type' => $condition->getType(),
                'value' => $condition->toArray(),
            ]);

            if (method_exists($condition, 'getProducts')) {
                $discountCondition->products()->attach($condition->getProducts());
            }

            if (method_exists($condition, 'getProductSets')) {
                $discountCondition->productSets()->attach($condition->getProductSets());
            }

            if (method_exists($condition, 'getRoles')) {
                $discountCondition->roles()->attach($condition->getRoles());
            }

            if (method_exists($condition, 'getUsers')) {
                $discountCondition->users()->attach($condition->getUsers());
            }
        }

        return $conditionGroup;
    }

    private function checkConditionOrderValue(DiscountCondition $condition, float $cartValue = 0): bool
    {
        $conditionDto = OrderValueConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        // TODO uwzględnić przy sprawdzaniu podatki $conditionDto->isIncludeTaxes()
        if (!$conditionDto->getMinValue() instanceof Missing && !$conditionDto->getMaxValue() instanceof Missing) {
            return ($cartValue >= $conditionDto->getMinValue() && $cartValue <= $conditionDto->getMaxValue()) ===
                $conditionDto->isIsInRange();
        }

        if (!$conditionDto->getMinValue() instanceof Missing) {
            return $cartValue >= $conditionDto->getMinValue() === $conditionDto->isIsInRange();
        }

        if (!$conditionDto->getMaxValue() instanceof Missing) {
            return $cartValue <= $conditionDto->getMaxValue() === $conditionDto->isIsInRange();
        }

        return false;
    }

    private function checkConditionUserInRole(DiscountCondition $condition): bool
    {
        $conditionDto = UserInRoleConditionDto::fromArray($condition->value + ['type' => $condition->type]);
        $user = Auth::user();

        if ($user) {
            /** @var Role $role */
            foreach ($user->roles as $role) {
                if (in_array($role->getKey(), $conditionDto->getRoles()) === $conditionDto->isIsAllowList()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkConditionUserIn(DiscountCondition $condition): bool
    {
        $conditionDto = UserInConditionDto::fromArray($condition->value + ['type' => $condition->type]);
        if (Auth::user()) {
            return in_array(Auth::id(), $conditionDto->getUsers()) === $conditionDto->isIsAllowList();
        }
        return false;
    }

    private function checkConditionProductInSet(DiscountCondition $condition, array $productIds): bool
    {
        $conditionDto = ProductInSetConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        foreach ($productIds as $productId) {
            $product = Product::findOrFail($productId);
            $productSetsProduct = $product->sets()->whereIn('id', $conditionDto->getProductSets())->count();

            // Produkt należy do co najmniej jednej kolekcji
            if ($conditionDto->isIsAllowList() && $productSetsProduct > 0) {
                return true;
            }

            // Produkt nie należy do co najmniej jednej kolekcji
            if (!$conditionDto->isIsAllowList() && $productSetsProduct < count($conditionDto->getProductSets())) {
                return true;
            }
        }

        return false;
    }

    private function checkConditionProductIn(DiscountCondition $condition, array $productIds): bool
    {
        $conditionDto = ProductInConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        foreach ($productIds as $productId) {
            if (in_array($productId, $conditionDto->getProducts()) === $conditionDto->isIsAllowList()) {
                return true;
            }
        }

        return false;
    }

    private function checkConditionDateBetween(DiscountCondition $condition): bool
    {
        $conditionDto = DateBetweenConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        $actualDate = Carbon::now();

        $startAt = $conditionDto->getStartAt();
        $endAt = $conditionDto->getEndAt();

        $startAt = !$startAt instanceof Missing && !Str::contains($startAt, ':')
            ? Str::before($startAt, 'T'). 'T00:00:00' : $startAt;

        $endAt = !$endAt instanceof Missing && !Str::contains($endAt, ':')
            ? Str::before($endAt, 'T'). 'T23:59:59' : $endAt;

        if (!$startAt instanceof Missing && !$endAt instanceof Missing) {
            return $actualDate
                ->between($startAt, $endAt) === $conditionDto->isIsInRange();
        }

        if (!$startAt instanceof Missing) {
            return $actualDate->greaterThanOrEqualTo($startAt) === $conditionDto->isIsInRange();
        }

        if (!$endAt instanceof Missing) {
            return $actualDate->lessThanOrEqualTo($endAt) === $conditionDto->isIsInRange();
        }
        return false;
    }

    private function checkConditionTimeBetween(DiscountCondition $condition): bool
    {
        $conditionDto = TimeBetweenConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        $actualTime = Carbon::now();

        $startAt = $conditionDto->getStartAt() instanceof Missing ?
            null : Carbon::now()->setTimeFromTimeString($conditionDto->getStartAt());
        $endAt = $conditionDto->getEndAt() instanceof Missing ?
            null : Carbon::now()->setTimeFromTimeString($conditionDto->getEndAt());

        if ($startAt !== null && $endAt !== null) {
            if ($endAt->lessThanOrEqualTo($startAt)) {
                $startAt = $startAt->subDay();
            }
            return $actualTime->between($startAt, $endAt) === $conditionDto->isIsInRange();
        }

        if ($startAt !== null) {
            return $actualTime->greaterThanOrEqualTo($startAt) === $conditionDto->isIsInRange();
        }

        if ($endAt !== null) {
            return $actualTime->lessThanOrEqualTo($endAt) === $conditionDto->isIsInRange();
        }

        return false;
    }

    private function checkConditionMaxUses(DiscountCondition $condition): bool
    {
        $conditionDto = MaxUsesConditionDto::fromArray($condition->value + ['type' => $condition->type]);
        return $condition->conditionGroup->discounts()->first()->orders()->count() < $conditionDto->getMaxUses();
    }

    private function checkConditionMaxUsesPerUser(DiscountCondition $condition): bool
    {
        $conditionDto = MaxUsesPerUserConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        if (Auth::user()) {
            return $condition
                ->conditionGroup
                ->discounts()
                ->first()
                ->orders()
                ->whereHasMorph('buyer', [User::class, App::class], function (Builder $query): void {
                    $query->where('buyer_id', Auth::id());
                })
                ->count() < $conditionDto->getMaxUses();
        }

        return false;
    }

    private function checkConditionWeekdayIn(DiscountCondition $condition): bool
    {
        $conditionDto = WeekDayInConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        // W Carbon niedziela jest pod indeksem 0
        return $conditionDto->getWeekday()[Carbon::now()->dayOfWeek];
    }

    private function checkConditionCartLength(DiscountCondition $condition, int $cartLength): bool
    {
        $conditionDto = CartLengthConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        if (!$conditionDto->getMinValue() instanceof Missing && !$conditionDto->getMaxValue() instanceof Missing) {
            return $cartLength >= $conditionDto->getMinValue() && $cartLength <= $conditionDto->getMaxValue();
        }

        if (!$conditionDto->getMinValue() instanceof Missing) {
            return $cartLength >= $conditionDto->getMinValue();
        }

        if (!$conditionDto->getMaxValue() instanceof Missing) {
            return $cartLength <= $conditionDto->getMaxValue();
        }

        return false;
    }

    private function checkConditionCouponsCount(DiscountCondition $condition, int $couponsCount): bool
    {
        $conditionDto = CouponsCountConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        if (!$conditionDto->getMinValue() instanceof Missing && !$conditionDto->getMaxValue() instanceof Missing) {
            return $couponsCount >= $conditionDto->getMinValue() && $couponsCount <= $conditionDto->getMaxValue();
        }

        if (!$conditionDto->getMinValue() instanceof Missing) {
            return $couponsCount >= $conditionDto->getMinValue();
        }

        if (!$conditionDto->getMaxValue() instanceof Missing) {
            return $couponsCount <= $conditionDto->getMaxValue();
        }

        return false;
    }
}
