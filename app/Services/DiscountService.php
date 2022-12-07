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
use App\Exceptions\ServerException;
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
use App\Models\ProductSet;
use App\Models\Role;
use App\Models\SalesShortResource;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\SettingsServiceContract;
use App\Services\Contracts\ShippingTimeDateServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class DiscountService implements DiscountServiceContract
{
    public function __construct(
        private SettingsServiceContract $settingsService,
        private MetadataServiceContract $metadataService,
        private ShippingTimeDateServiceContract $shippingTimeDateService
    ) {
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
                $discount->update(['code' => $discount->code . '-' . Carbon::now()->timestamp]);
                CouponDeleted::dispatch($discount);
            } else {
                CalculateDiscount::dispatchIf(
                    $discount->active && $discount->target_type->is(DiscountTargetType::PRODUCTS),
                    $discount,
                );
                SaleDeleted::dispatch($discount);
            }
        }
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

    public function checkCondition(
        DiscountCondition $condition,
        ?CartOrderDto $dto = null,
        float $cartValue = 0,
    ): bool {
        return match ($condition->type->value) {
            ConditionType::ORDER_VALUE => $this->checkConditionOrderValue($condition, $cartValue),
            ConditionType::USER_IN_ROLE => $this->checkConditionUserInRole($condition),
            ConditionType::USER_IN => $this->checkConditionUserIn($condition),
            ConditionType::PRODUCT_IN_SET => $this->checkConditionProductInSet(
                $condition,
                $dto?->getProductIds() ?? []
            ),
            ConditionType::PRODUCT_IN => $this->checkConditionProductIn($condition, $dto?->getProductIds() ?? []),
            ConditionType::DATE_BETWEEN => $this->checkConditionDateBetween($condition),
            ConditionType::TIME_BETWEEN => $this->checkConditionTimeBetween($condition),
            ConditionType::MAX_USES => $this->checkConditionMaxUses($condition),
            ConditionType::MAX_USES_PER_USER => $this->checkConditionMaxUsesPerUser($condition),
            ConditionType::WEEKDAY_IN => $this->checkConditionWeekdayIn($condition),
            ConditionType::CART_LENGTH => $this->checkConditionCartLength($condition, $dto?->getCartLength() ?? 0),
            ConditionType::COUPONS_COUNT => $this->checkConditionCouponsCount(
                $condition,
                $dto?->getCoupons() !== null && (!$dto->getCoupons() instanceof Missing)
                    ? count($dto->getCoupons()) : 0,
            ),
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
        if ($discount->active) {
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
        return false;
    }

    /**
     * @throws StoreException
     * @throws ClientException
     */
    public function calcOrderProductsAndTotalDiscounts(Order $order, OrderDto $orderDto): Order
    {
        return $this->calcOrderDiscounts($order, $orderDto, [
            DiscountTargetType::fromValue(DiscountTargetType::PRODUCTS),
            DiscountTargetType::fromValue(DiscountTargetType::CHEAPEST_PRODUCT),
            DiscountTargetType::fromValue(DiscountTargetType::ORDER_VALUE),
        ]);
    }

    /**
     * @throws StoreException
     * @throws ClientException
     */
    public function calcOrderShippingDiscounts(Order $order, OrderDto $orderDto): Order
    {
        return $this->calcOrderDiscounts($order, $orderDto, [
            DiscountTargetType::fromValue(DiscountTargetType::SHIPPING_PRICE),
        ]);
    }

    public function calcCartDiscounts(CartDto $cart, Collection $products): CartResource
    {
        $discounts = $this->getActiveSalesAndCoupons($cart->getCoupons());
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
            $cartItems[] = new CartItemResponse($cartItem->getCartItemId(), $price, $price, $cartItem->getQuantity());
        }
        $cartShippingTimeAndDate = $this->shippingTimeDateService->getTimeAndDateForCart($cart, $products);

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
            $cartShippingTimeAndDate['shipping_time'] ?? null,
            $cartShippingTimeAndDate['shipping_date'] ?? null,
            $summary,
        );

        if ($cartResource->items->isEmpty()) {
            return $cartResource;
        }

        foreach ($discounts as $discount) {
            if ($this->checkConditionGroups($discount, $cart, $cartResource->cart_total)) {
                $cartResource = $this->applyDiscountOnCart($discount, $cart, $cartResource);
                $newSummary = $cartResource->cart_total + $cartResource->shipping_price;
                $appliedDiscount = round($summary - $newSummary, 2, PHP_ROUND_HALF_UP);

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

        $cartResource->cart_total = round($cartResource->cart_total, 2, PHP_ROUND_HALF_UP);
        $cartResource->shipping_price = round($cartResource->shipping_price, 2, PHP_ROUND_HALF_UP);

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
            /** @var Schema $schema */
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
        $salesWithBlockList = $this->getSalesWithBlockList();
        foreach ($products as $product) {
            $this->applyAllDiscountsOnProduct($product, $salesWithBlockList);
        }
    }

    public function applyDiscountsOnProduct(Product $product, bool $reindex = true): void
    {
        $salesWithBlockList = $this->getSalesWithBlockList();
        $this->applyAllDiscountsOnProduct($product, $salesWithBlockList, $reindex);
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
            fn ($value) => $value->cartitem_id === $cartItem->getCartItemId(),
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
            && $refreshedOrder?->discounts->count() === 0) {
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
            ->active()
            ->where('target_type', DiscountTargetType::PRODUCTS)
            ->where(
                fn (Builder $query) => $query
                    ->whereHas('conditionGroups', function ($query): void {
                    $query
                        ->whereHas('conditions', function ($query): void {
                            $query
                                ->where('type', ConditionType::DATE_BETWEEN)
                                ->orWhere('type', ConditionType::TIME_BETWEEN)
                                ->orWhere('type', ConditionType::WEEKDAY_IN);
                        });
                    })
                ->orWhereDoesntHave('conditionGroups')
            )
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
            return $sale->conditionGroups->isEmpty();
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

    public function calculateDiscount(Discount $discount, bool $updated): void
    {
        // If discount has conditions based on time, then must be added or removed from cache
        $this->checkIsDiscountActive($discount);

        $salesWithBlockList = $this->getSalesWithBlockList();
        $products = Collection::make();

        // if job is called after update, then calculate discount for all products,
        // because it may change the list of related products or target_is_allow_list value
        if (!$updated && $discount->active) {
            $products = $this->allDiscountProductsIds($discount);
        }

        $this->applyDiscountsOnProductsLazy($products, $salesWithBlockList);
    }

    public function checkActiveSales(): void
    {
        /** @var Collection<int, mixed> $activeSales */
        $activeSales = Cache::get('sales.active', Collection::make());

        $oldActiveSales = Collection::make($activeSales);

        $activeSalesIds = $this->activeSales()->pluck('id');
        $saleIds = $activeSalesIds
            ->diff($oldActiveSales)
            ->merge($oldActiveSales->diff($activeSalesIds));

        $sales = Discount::query()
            ->whereIn('id', $saleIds)
            ->with(['products', 'productSets', 'productSets.products'])
            ->get();

        $products = Collection::make();

        foreach ($sales as $sale) {
            $products = $products->merge($this->allDiscountProductsIds($sale));
        }

        $products = $products->unique();
        $this->applyDiscountsOnProductsLazy(
            $products,
            $this->getSalesWithBlockList(),
        );

        Cache::put('sales.active', $activeSalesIds);
    }

    public function applyAllDiscountsOnProduct(
        Product $product,
        Collection $salesWithBlockList,
        bool $reindex = true,
    ): void {
        $sales = $this->sortDiscounts($product->allProductSales($salesWithBlockList));

        // prevent error when price_min or price_max is null
        $minPriceDiscounted = $product->price_min_initial ?? $product->price;
        $maxPriceDiscounted = $product->price_max_initial ?? $product->price;

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

        // + 1 query for product
        $product->update([
            'price_min' => $minPriceDiscounted,
            'price_max' => $maxPriceDiscounted,
        ]);
        // detach and attach only add 2 queries to database, sync add 1 query for every element in given array,
        $product->sales()->detach();
        $product->sales()->attach($productSales->pluck('id'));

        if ($reindex) {
            $product->searchable();
        }
    }

    /**
     * @param array<DiscountTargetType> $targetTypes
     *
     * @throws StoreException
     * @throws ClientException
     */
    private function calcOrderDiscounts(Order $order, OrderDto $orderDto, array $targetTypes = []): Order
    {
        $coupons = $orderDto->getCoupons() instanceof Missing ? [] : $orderDto->getCoupons();
        $sales = $orderDto->getSaleIds() instanceof Missing ? [] : $orderDto->getSaleIds();
        $discounts = $this->getActiveSalesAndCoupons($coupons, $targetTypes);

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

        $refreshed = $order->fresh();
        if ($refreshed?->discounts->count() === 0) {
            $order = $this->roundProductPrices($order);
        }

        $order->cart_total = round($order->cart_total, 2, PHP_ROUND_HALF_UP);
        $order->shipping_price = round($order->shipping_price, 2, PHP_ROUND_HALF_UP);
        $order->summary = $order->cart_total + $order->shipping_price;
        $order->paid = $order->summary <= 0;

        return $order;
    }

    private function allDiscountProductsIds(Discount $discount): Collection
    {
        $products = $discount->allProductsIds();

        if (!$discount->target_is_allow_list) {
            $products = Product::query()->whereNotIn('id', $products)->pluck('id');
        }

        // Return only ids of products, that should be discounted
        return $products;
    }

    private function checkIsDiscountActive(Discount $discount): void
    {
        if ($this->checkDiscountHasTimeConditions($discount)) {
            /** @var Collection<int, mixed> $activeSales */
            $activeSales = Cache::get('sales.active', Collection::make());

            if ($discount->active && $this->checkDiscountTimeConditions($discount)) {
                if (!$activeSales->contains($discount->getKey())) {
                    $activeSales->push($discount->getKey());
                }
            } else {
                if ($activeSales->contains($discount->getKey())) {
                    $activeSales = $activeSales->reject(
                        fn ($value, $key) => $value === $discount->getKey(),
                    );
                }
            }
            Cache::put('sales.active', $activeSales);
        }
    }

    private function roundProductPrices(Order $order): Order
    {
        // If cheapest product has been split, it will not be returned by $order->products,
        // and $order->products()->get() has products without discount, if order will be saved at this moment,
        // all products in database should be updated, and split product will be returned by $order->products
        $order->push();
        $order->refresh();
        $totalPrice = 0;
        foreach ($order->products as $product) {
            $product->price = round($product->price, 2, PHP_ROUND_HALF_UP);
            $totalPrice += $product->price * $product->quantity;
        }

        $order->cart_total = round($totalPrice, 2, PHP_ROUND_HALF_UP);

        return $order;
    }

    private function calcProductPriceDiscount(Discount $discount, float $price, float $minimalProductPrice): float
    {
        $price -= $this->calc($price, $discount);
        return max($price, $minimalProductPrice);
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
            ConditionType::ORDER_VALUE => false,
            ConditionType::PRODUCT_IN_SET => false,
            ConditionType::PRODUCT_IN => false,
            ConditionType::USER_IN_ROLE => $this->checkConditionUserInRole($condition),
            ConditionType::USER_IN => $this->checkConditionUserIn($condition),
            ConditionType::DATE_BETWEEN => $this->checkConditionDateBetween($condition),
            ConditionType::TIME_BETWEEN => $this->checkConditionTimeBetween($condition),
            ConditionType::MAX_USES => $this->checkConditionMaxUses($condition),
            ConditionType::MAX_USES_PER_USER => $this->checkConditionMaxUsesPerUser($condition),
            ConditionType::WEEKDAY_IN => $this->checkConditionWeekdayIn($condition),
            ConditionType::CART_LENGTH => false,
            ConditionType::COUPONS_COUNT => $this->checkConditionCouponsCount($condition, 0),
            // don't add default false, better to crash site than got unexpected behaviour
            default => throw new ServerException('Unknown condition type: ' . $condition->type->value),
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

        // Adding a discount to orderProduct
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

        if ($product !== null) {
            $minimalProductPrice = $this->settingsService->getMinimalPrice('minimal_product_price');

            if ($product->quantity > 1 && $product->price !== $minimalProductPrice) {
                $product->update(['quantity' => $product->quantity - 1]);

                /** @var OrderProduct $newProduct */
                $newProduct = $order->products()->create([
                    'product_id' => $product->product_id,
                    'quantity' => 1,
                    'price' => $product->price,
                    'price_initial' => $product->price_initial,
                    'name' => $product->name,
                    'base_price_initial' => $product->price,
                    'base_price' => $product->price,
                    'vat_rate' => $product->vat_rate,
                ]);

                $product->discounts->each(function (Discount $discount) use ($newProduct): void {
                    // @phpstan-ignore-next-line
                    $this->attachDiscount($newProduct, $discount, $discount->pivot->applied_discount);
                });

                $product = $newProduct;
            }

            $price = $product->price ?? 0;

            if ($price !== $minimalProductPrice) {
                $this->calcOrderProductDiscount($product, $discount);
                $product->save();
            }

            $order->cart_total -= ($price - $product->price) * $product->quantity;
        }

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

            $cartResource->shipping_price = round($cartResource->shipping_price, 2, PHP_ROUND_HALF_UP);
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
        $cartResource->cart_total = round($cartResource->cart_total, 2, PHP_ROUND_HALF_UP);
        return $cartResource;
    }

    private function applyDiscountOnCartItems(Discount $discount, CartDto $cartDto, CartResource $cart): CartResource
    {
        $cartItems = [];
        $cartValue = 0;

        /** @var CartItemDto $item */
        foreach ($cartDto->getItems() as $item) {
            $cartItem = $cart->items->filter(function ($value, $key) use ($item) {
                return $value->cartitem_id === $item->getCartItemId();
            })->first();

            if ($cartItem === null) {
                continue;
            }

            $cartItem = $this->applyDiscountOnCartItem($discount, $item, $cart);

            $cartItems[] = $cartItem;

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

        $minimalProductPrice = $this->settingsService->getMinimalPrice('minimal_product_price');

        if ($cartItem->quantity > 1 && $cartItem->price_discounted !== $minimalProductPrice) {
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

        if ($price !== $minimalProductPrice) {
            $newPrice = round($price - $this->calc($price, $discount), 2, PHP_ROUND_HALF_UP);

            $cartItem->price_discounted = max($newPrice, $minimalProductPrice);

            $cart->cart_total -= ($price - $cartItem->price_discounted) * $cartItem->quantity;
        }

        return $cart;
    }

    /**
     * @param array<DiscountTargetType> $targetTypes
     */
    private function getActiveSalesAndCoupons(array|Missing $couponIds, array $targetTypes = []): Collection
    {
        // Get all active discounts
        $salesQuery = Discount::query()
            ->active()
            ->where('code', null)
            ->with(['orders', 'products', 'productSets', 'conditionGroups', 'conditionGroups.conditions']);
        if (count($targetTypes)) {
            $salesQuery = $salesQuery->whereIn('target_type', $targetTypes);
        }
        $sales = $salesQuery->get();

        // No coupons used
        if ($couponIds instanceof Missing) {
            return $this->sortDiscounts($sales);
        }

        // Get all active coupons
        $couponsQuery = Discount::whereIn('code', $couponIds)
            ->active()
            ->with(['orders', 'products', 'productSets', 'conditionGroups', 'conditionGroups.conditions']);
        if (count($targetTypes)) {
            $couponsQuery = $couponsQuery->whereIn('target_type', $targetTypes);
        }
        $coupons = $couponsQuery->get();

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
            $price = max($price, $minimalProductPrice);
        }

        return round($price, 2, PHP_ROUND_HALF_UP);
    }

    private function checkIsProductInDiscount(string $productId, Discount $discount): bool
    {
        $inDiscount = $this->checkIsProductInDiscountProducts($productId, $discount);

        if ($inDiscount !== $discount->target_is_allow_list) {
            return $this->checkIsProductInDiscountProductSets(
                $productId,
                $discount->productSets,
                $discount->target_is_allow_list
            );
        }

        return $inDiscount;
    }

    private function checkIsProductInDiscountProducts(string $productId, Discount $discount): bool
    {
        return in_array($productId, $discount->products->pluck('id')->all()) === $discount->target_is_allow_list;
    }

    private function checkIsProductInDiscountProductSets(
        string $productId,
        Collection $discountProductSets,
        bool $allowList
    ): bool {
        $product = Product::where('id', $productId)->firstOrFail();

        $productSets = $product->sets()->with('parent')->get();
        $diffCount = $productSets->pluck('id')->diff($discountProductSets->pluck('id')->all())->count();

        // some product sets are in discount
        if ($diffCount < $productSets->count()) {
            return $allowList;
        }

        foreach ($productSets as $productSet) {
            $result = $this->checkProductSetParentInDiscount($productSet, $discountProductSets, $allowList);
            if ($result === $allowList) {
                return $result;
            }
        }

        return !$allowList;
    }

    private function checkProductSetParentInDiscount(
        ProductSet $productSet,
        Collection $productSets,
        bool $allowList
    ): bool {
        if ($productSet->parent) {
            if ($productSets->contains($productSet->parent->id)) {
                return $allowList;
            }
            return $this->checkProductSetParentInDiscount($productSet->parent, $productSets, $allowList);
        }
        return !$allowList;
    }

    private function createConditionGroupsToAttach(array $conditions): array
    {
        $result = [];
        foreach ($conditions as $condition) {
            $result[] = $this->createConditionGroup($condition);
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
        $productSets = ProductSet::whereIn('id', $conditionDto->getProductSets())->get();

        foreach ($productIds as $productId) {
            $result = $this->checkIsProductInDiscountProductSets(
                $productId,
                $productSets,
                $conditionDto->isIsAllowList()
            );

            if ($result === $conditionDto->isIsAllowList()) {
                return $result;
            }
        }

        return !$conditionDto->isIsAllowList();
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
        return $condition->conditionGroup?->discounts()->first()?->orders()->count() < $conditionDto->getMaxUses();
    }

    private function checkConditionMaxUsesPerUser(DiscountCondition $condition): bool
    {
        $conditionDto = MaxUsesPerUserConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        if (Auth::user()) {
            return $condition
                ->conditionGroup
                ?->discounts()
                ->first()
                ?->orders()
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

        // In Carbon week starts with sunday (index - 0)
        return $conditionDto->getWeekday()[Carbon::now()->dayOfWeek];
    }

    private function checkConditionCartLength(DiscountCondition $condition, int|float $cartLength): bool
    {
        $conditionDto = CartLengthConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        return $this->checkConditionLength($conditionDto, $cartLength);
    }

    private function checkConditionCouponsCount(DiscountCondition $condition, int|float $couponsCount): bool
    {
        $conditionDto = CouponsCountConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        return $this->checkConditionLength($conditionDto, $couponsCount);
    }

    private function checkConditionLength(
        CartLengthConditionDto|CouponsCountConditionDto $conditionDto,
        int|float $count,
    ): bool {
        if (!$conditionDto->getMinValue() instanceof Missing && !$conditionDto->getMaxValue() instanceof Missing) {
            return $count >= $conditionDto->getMinValue() && $count <= $conditionDto->getMaxValue();
        }

        if (!$conditionDto->getMinValue() instanceof Missing) {
            return $count >= $conditionDto->getMinValue();
        }

        if (!$conditionDto->getMaxValue() instanceof Missing) {
            return $count <= $conditionDto->getMaxValue();
        }

        return false;
    }

    private function getSalesWithBlockList(): Collection
    {
        return Discount::where('code', null)
            ->active()
            ->where('target_type', DiscountTargetType::PRODUCTS)
            ->where('target_is_allow_list', false)->with(['products', 'productSets', 'productSets.products'])->get();
    }

    private function applyDiscountsOnProductsLazy(Collection $productIds, Collection $salesWithBlockList): void
    {
        $productQuery = Product::with([
            'discounts',
            'sets',
            'sets.discounts',
            'sets.parent',
        ]);

        if ($productIds->isNotEmpty()) {
            $productQuery = $productQuery->whereIn('id', $productIds);
        }

        $productQuery->chunk(100, function ($products) use ($salesWithBlockList): void {
            foreach ($products as $product) {
                $this->applyAllDiscountsOnProduct($product, $salesWithBlockList, false);
            }

            $products->searchable();
        });
    }
}
