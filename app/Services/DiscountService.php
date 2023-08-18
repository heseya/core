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
use App\Dtos\ProductPriceDto;
use App\Dtos\SaleDto;
use App\Dtos\SaleIndexDto;
use App\Dtos\TimeBetweenConditionDto;
use App\Dtos\UserInConditionDto;
use App\Dtos\UserInRoleConditionDto;
use App\Dtos\WeekDayInConditionDto;
use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\Product\ProductPriceType;
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
use App\Models\Role;
use App\Models\SalesShortResource;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\DiscountRepository;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\SettingsServiceContract;
use App\Services\Contracts\ShippingTimeDateServiceContract;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\ProductSet\ProductSet;
use Domain\Seo\SeoMetadataService;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\Str;

readonly class DiscountService implements DiscountServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
        private SettingsServiceContract $settingsService,
        private SeoMetadataService $seoMetadataService,
        private ShippingTimeDateServiceContract $shippingTimeDateService,
        private ProductRepositoryContract $productRepository,
        private DiscountRepository $discountRepository,
    ) {}

    public function index(CouponIndexDto|SaleIndexDto $dto): LengthAwarePaginator
    {
        return Discount::searchByCriteria($dto->toArray())
            ->orderBy('updated_at', 'DESC')
            ->with(['orders', 'products', 'productSets', 'conditionGroups', 'shippingMethods', 'metadata'])
            ->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @throws ClientException
     */
    public function store(CouponDto|SaleDto $dto): Discount
    {
        if ($dto->amounts instanceof Missing && $dto->percentage instanceof Missing) {
            throw new ClientException('You need either percentage or amount values for the discount value');
        }

        /** @var Discount $discount */
        $discount = Discount::query()->create($dto->toArray());

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

        if (!($dto->getSeo() instanceof Missing)) {
            $this->seoMetadataService->createOrUpdateFor($discount, $dto->getSeo());
        }

        if (!($dto->amounts instanceof Missing)) {
            $this->discountRepository->setDiscountAmounts($discount->getKey(), $dto->amounts);
        }

        if ($dto instanceof CouponDto) {
            CouponCreated::dispatch($discount);
        } else {
            CalculateDiscount::dispatchIf(
                $discount->target_type === DiscountTargetType::PRODUCTS,
                $discount,
            );
            SaleCreated::dispatch($discount);
        }

        return $discount;
    }

    public function update(Discount $discount, CouponDto|SaleDto $dto): Discount
    {
        $discount->fill($dto->toArray());

        if ($dto->percentage instanceof Missing && !($dto->amounts instanceof Missing)) {
            $discount->percentage = null;
        }

        $discount->save();

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

        if (!($dto->getSeo() instanceof Missing)) {
            $this->seoMetadataService->createOrUpdateFor($discount, $dto->getSeo());
        }

        if (!($dto->amounts instanceof Missing)) {
            $this->discountRepository->setDiscountAmounts($discount->getKey(), $dto->amounts);
        } elseif (!($dto->percentage instanceof Missing)) {
            $discount->amounts()->delete();
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
                    $discount->active && $discount->target_type === DiscountTargetType::PRODUCTS,
                    $discount,
                );
                SaleDeleted::dispatch($discount);
            }
        }
    }

    private function getSalesWithBlockList(): Collection
    {
        return Discount::query()
            ->whereNull('code')
            ->where('active', '=', true)
            ->where('target_type', '=', DiscountTargetType::PRODUCTS->value)
            ->where('target_is_allow_list', '=', false)
            ->with(['products', 'productSets', 'productSets.products'])
            ->get();
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

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws ServerException
     * @throws UnknownCurrencyException
     */
    private function calcPrice(Money $price, string $productId, Discount $discount): Money
    {
        $minimalProductPrice = Money::ofMinor(1, $price->getCurrency());

        if (!$price->isEqualTo($minimalProductPrice) && $this->checkIsProductInDiscount($productId, $discount)) {
            $price = $price->minus($this->calc($price, $discount));
            $price = Money::max($price, $minimalProductPrice);
        }

        return $price;
    }

    /**
     * @throws MathException
     * @throws ServerException
     */
    public function calc(Money $value, Discount $discount): Money
    {
        $currency = Currency::tryFrom($value->getCurrency()->getCurrencyCode());

        if ($currency === null) {
            throw new ServerException(Exceptions::SERVER_PRICE_UNKNOWN_CURRENCY);
        }

        $percentage = $discount->pivot->percentage ?? $discount->percentage;

        if ($percentage !== null) {
            $percentage = BigDecimal::of($percentage)->dividedBy(100, roundingMode: RoundingMode::HALF_DOWN);

            // It's debatable which rounding mode we use based on context
            // This fits with current tests
            $value = $value->multipliedBy($percentage, RoundingMode::HALF_DOWN);
        } else {
            [$amount] = $this->discountRepository::getDiscountAmounts($discount->getKey(), $currency);

            $value = $amount->value;
        }

        return $value;
    }

    /**
     * @throws ServerException
     * @throws MoneyMismatchException
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws ClientException
     * @throws NumberFormatException
     */
    public function calculateDiscount(Discount $discount, bool $updated): void
    {
        // If discount has conditions based on time, then must be added or removed from cache
        $this->checkIsDiscountActive($discount);

        // Why do I need this
        $salesWithBlockList = $this->getSalesWithBlockList();
        $products = Collection::make();

        // if job is called after update, then calculate discount for all products,
        // because it may change the list of related products or target_is_allow_list value
        if (!$updated && $discount->active) {
            $products = $this->allDiscountProductsIds($discount);
        }

        $this->applyDiscountsOnProductsLazy($products, $salesWithBlockList);
    }

    /**
     * @throws StoreException
     * @throws ClientException
     */
    public function calcOrderProductsAndTotalDiscounts(Order $order, OrderDto $orderDto): Order
    {
        return $this->calcOrderDiscounts($order, $orderDto, [
            DiscountTargetType::PRODUCTS,
            DiscountTargetType::CHEAPEST_PRODUCT,
            DiscountTargetType::ORDER_VALUE,
        ]);
    }

    /**
     * @throws StoreException
     * @throws ClientException
     */
    public function calcOrderShippingDiscounts(Order $order, OrderDto $orderDto): Order
    {
        return $this->calcOrderDiscounts($order, $orderDto, [
            DiscountTargetType::SHIPPING_PRICE,
        ]);
    }

    /**
     * @throws MathException
     * @throws StoreException
     * @throws MoneyMismatchException
     * @throws UnknownCurrencyException
     * @throws DtoException
     */
    public function calcCartDiscounts(CartDto $cart, Collection $products): CartResource
    {
        $discounts = $this->getActiveSalesAndCoupons($cart->getCoupons());

        /** @var ?ShippingMethod $shippingMethod */
        $shippingMethod = is_string($cart->getShippingMethodId())
            ? ShippingMethod::query()->findOrFail($cart->getShippingMethodId())
            : null;

        /** @var ?ShippingMethod $shippingMethodDigital */
        $shippingMethodDigital = is_string($cart->getDigitalShippingMethodId())
            ? ShippingMethod::query()->findOrFail($cart->getDigitalShippingMethodId())
            : null;

        /**
         * TODO: Cart needs currency.
         */
        $currency = Currency::DEFAULT;
        $cartItems = [];
        $cartValue = Money::zero($currency->value);

        foreach ($cart->getItems() as $cartItem) {
            $product = $products->firstWhere('id', $cartItem->getProductId());

            if (!($product instanceof Product)) {
                // skip when product is not available
                continue;
            }

            $prices = $this->productRepository->getProductPrices($product->getKey(), [
                ProductPriceType::PRICE_BASE,
            ], $currency);

            $price = $prices->get(ProductPriceType::PRICE_BASE->value)?->firstOrFail()?->value ?? throw new ItemNotFoundException();

            foreach ($cartItem->getSchemas() as $schemaId => $value) {
                /** @var Schema $schema */
                $schema = $product->schemas()->findOrFail($schemaId);

                $price = $price->plus(
                    $schema->getPrice($value, $cartItem->getSchemas(), $currency),
                );
            }
            $cartValue = $cartValue->plus($price->multipliedBy($cartItem->getQuantity()));
            $cartItems[] = new CartItemResponse(
                $cartItem->getCartItemId(),
                $price->getAmount()->toFloat(),
                $price->getAmount()->toFloat(),
                $cartItem->getQuantity(),
            );
        }
        $cartShippingTimeAndDate = $this->shippingTimeDateService->getTimeAndDateForCart($cart, $products);

        $shippingPrice = $shippingMethod?->getPrice($cartValue) ?? Money::zero($currency->value);
        $shippingPrice = $shippingPrice->plus(
            $shippingMethodDigital?->getPrice($cartValue) ?? Money::zero($currency->value),
        );

        $summary = $cartValue->plus($shippingPrice);

        $cartResource = new CartResource(
            Collection::make($cartItems),
            Collection::make(),
            Collection::make(),
            $cartValue->getAmount()->toFloat(),
            $cartValue->getAmount()->toFloat(),
            $shippingPrice->getAmount()->toFloat(),
            $shippingPrice->getAmount()->toFloat(),
            $cartShippingTimeAndDate['shipping_time'] ?? null,
            $cartShippingTimeAndDate['shipping_date'] ?? null,
            $summary->getAmount()->toFloat(),
        );

        if ($cartResource->items->isEmpty()) {
            return $cartResource;
        }

        foreach ($discounts as $discount) {
            if (
                $this->checkDiscountTarget($discount, $cart)
                && $this->checkConditionGroups($discount, $cart, $cartResource->cart_total)
            ) {
                $cartResource = $this->applyDiscountOnCart($discount, $cart, $cartResource);
                $newSummary = Money::of($cartResource->cart_total + $cartResource->shipping_price, $currency->value);
                $appliedDiscount = $summary->minus($newSummary);

                if ($discount->code !== null) {
                    $cartResource->coupons->push(
                        new CouponShortResource(
                            $discount->getKey(),
                            $discount->name,
                            $appliedDiscount->getAmount()->toFloat(),
                            $discount->code,
                        )
                    );
                } else {
                    $cartResource->sales->push(
                        new SalesShortResource(
                            $discount->getKey(),
                            $discount->name,
                            $appliedDiscount->getAmount()->toFloat(),
                        )
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

    /**
     * @return ProductPriceDto[]
     */
    public function calcProductsListDiscounts(Collection $products): array
    {
        $salesWithBlockList = $this->getSalesWithBlockList();

        return $products->map(function (Product $product) use ($salesWithBlockList) {
            /**
             * @var PriceDto[] $minPriceDiscounted
             * @var PriceDto[] $maxPriceDiscounted
             */
            [$minPriceDiscounted, $maxPriceDiscounted] = $this->calcAllDiscountsOnProduct(
                $product,
                $salesWithBlockList,
                true,
            );

            // TODO: Fix this with multiple currencies
            return new ProductPriceDto(
                $product->getKey(),
                $minPriceDiscounted[0]->value->getAmount()->toFloat(),
                $maxPriceDiscounted[0]->value->getAmount()->toFloat(),
            );
        })->toArray();
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function calcAppliedDiscount(Money $price, Money $appliedDiscount, string $setting): Money
    {
        $minimalPrice = Money::ofMinor(1, $price->getCurrency());

        $maximumDiscount = $price->minus($minimalPrice);

        return $appliedDiscount->isGreaterThan($maximumDiscount) ? $maximumDiscount : $appliedDiscount;
    }

    /**
     * @throws MoneyMismatchException
     * @throws ServerException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     * @throws DtoException
     */
    private function calcAllDiscountsOnProduct(
        Product $product,
        Collection $salesWithBlockList,
        bool $calcForCurrentUser,
        Currency $currency = Currency::DEFAULT,
    ): array {
        $sales = $this->sortDiscounts($product->allProductSales($salesWithBlockList));

        $prices = $this->productRepository->getProductPrices(
            $product->getKey(),
            [
                ProductPriceType::PRICE_MIN_INITIAL,
                ProductPriceType::PRICE_MAX_INITIAL,
            ],
            $currency
        );

        $minPrices = $prices->get(ProductPriceType::PRICE_MIN_INITIAL->value, collect());
        $maxPrices = $prices->get(ProductPriceType::PRICE_MAX_INITIAL->value, collect());

        $minimalMonetaryValue = 1;

        $productSales = Collection::make();

        foreach ($sales as $sale) {
            if ($this->checkConditionGroupsForProduct($sale, $calcForCurrentUser)) {
                foreach ($minPrices as $index => $minPrice) {
                    $minimalProductPrice = Money::ofMinor(
                        $minimalMonetaryValue,
                        $minPrice->value->getCurrency(),
                    );

                    if (!$minPrice->value->isEqualTo($minimalProductPrice)) {
                        $minPrices[$index] = new PriceDto(
                            $this->calcProductPriceDiscount($sale, $minPrice->value, $minimalProductPrice),
                        );
                    }
                }

                foreach ($maxPrices as $index => $maxPrice) {
                    $minimalProductPrice = Money::ofMinor(
                        $minimalMonetaryValue,
                        $maxPrice->value->getCurrency(),
                    );

                    if (!$maxPrice->value->isEqualTo($minimalProductPrice)) {
                        $maxPrices[$index] = new PriceDto(
                            $this->calcProductPriceDiscount($sale, $maxPrice->value, $minimalProductPrice),
                        );
                    }
                }

                $productSales->push($sale);
            }
        }

        return [
            $minPrices,
            $maxPrices,
            $productSales,
        ];
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
                ($discount->code === null && in_array($discount->getKey(), $sales))
                || $discount->code !== null
            ) {
                [$type, $id] = $discount->code !== null ? ['coupon', $discount->code] : ['sale', $discount->getKey()];
                throw new ClientException(Exceptions::CLIENT_CANNOT_APPLY_SELECTED_DISCOUNT_TYPE, errorArray: ['type' => $type, 'id' => $id]);
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

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws ServerException
     */
    private function calcProductPriceDiscount(Discount $discount, Money $price, Money $minimalProductPrice): Money
    {
        $price = $price->minus($this->calc($price, $discount));

        return Money::max($price, $minimalProductPrice);
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     * @throws ServerException
     */
    private function calcOrderProductDiscount(
        OrderProduct $orderProduct,
        Discount $discount,
    ): void {
        $price = Money::of(
            $orderProduct->price,
            Currency::DEFAULT->value,
        );

        $appliedDiscount = $this->calcAppliedDiscount(
            $price,
            $this->calc($price, $discount),
            'minimal_product_price',
        );

        // TODO: Service shouldn't modify prices randomly
        $orderProduct->price = $price->minus($appliedDiscount)->getAmount()->toFloat();

        // TODO: Change to money
        // Adding a discount to orderProduct
        $this->attachDiscount($orderProduct, $discount, $appliedDiscount->getAmount()->toFloat());
    }

    /**
     * @param Collection<array-key, Discount> $discounts
     */
    private function sortDiscounts(Collection $discounts): Collection
    {
        // Sortowanie zniżek w kolejności naliczania (Target type ASC, Discount type ASC, Priority DESC)
        return $discounts->sortBy([
            fn (Discount $a, Discount $b) => $a->target_type->getPriority() <=> $b->target_type->getPriority(),
            fn (Discount $a, Discount $b) => ($a->percentage !== null ? 1 : 0) <=> ($b->percentage !== null ? 1 : 0),
            fn (Discount $a, Discount $b) => $b->priority <=> $a->priority,
        ]);
    }

    /**
     * @throws MoneyMismatchException
     * @throws ServerException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws MathException
     * @throws ClientException
     * @throws NumberFormatException
     */
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
                $this->applyAllDiscountsOnProduct($product, $salesWithBlockList);
            }
        });
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws DtoException
     * @throws ServerException
     */
    public function applyDiscountOnProduct(
        Product $product,
        OrderProductDto $orderProductDto,
        Discount $discount
    ): OrderProduct {
        // TODO: Get currency somehow
        $currency = Currency::DEFAULT;

        $prices = $this->productRepository->getProductPrices($product->getKey(), [
            ProductPriceType::PRICE_BASE,
        ], $currency);

        $price = $prices->get(ProductPriceType::PRICE_BASE->value, collect())->firstOrFail()->value;

        foreach ($orderProductDto->getSchemas() as $schemaId => $value) {
            /** @var Schema $schema */
            $schema = $product->schemas()->findOrFail($schemaId);

            $price = $price->plus($schema->getPrice($value, $orderProductDto->getSchemas()));
        }

        return new OrderProduct([
            'product_id' => $product->getKey(),
            'quantity' => $orderProductDto->getQuantity(),
            'price' => $this->calcPrice($price, $product->getKey(), $discount)->getAmount()->toFloat(),
        ]);
    }

    /**
     * @throws MoneyMismatchException
     * @throws ServerException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws ClientException
     * @throws NumberFormatException
     * @throws DtoException
     */
    public function applyDiscountsOnProducts(Collection $products, ?Currency $currency = null): void
    {
        $salesWithBlockList = $this->getSalesWithBlockList();
        foreach ($products as $product) {
            $this->applyAllDiscountsOnProduct($product, $salesWithBlockList, $currency);
        }
    }

    /**
     * @throws ServerException
     * @throws MoneyMismatchException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws ClientException
     * @throws NumberFormatException
     * @throws DtoException
     */
    public function applyDiscountsOnProduct(Product $product): void
    {
        $salesWithBlockList = $this->getSalesWithBlockList();
        $this->applyAllDiscountsOnProduct($product, $salesWithBlockList);
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
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

    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function applyDiscountOnCartItem(
        Discount $discount,
        CartItemDto $cartItem,
        CartResource $cart,
    ): CartItemResponse {
        /** @var CartItemResponse $result */
        $result = $cart->items->filter(
            fn ($value) => $value->cartitem_id === $cartItem->getCartItemId(),
        )->first();

        $priceDiscounted = Money::of(
            $result->price_discounted,
            Currency::DEFAULT->value,
        );

        $result->price_discounted = $this->calcPrice(
            $priceDiscounted,
            $cartItem->getProductId(),
            $discount,
        )->getAmount()->toFloat();

        return $result;
    }

    /**
     * @throws ClientException
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function applyDiscountOnOrder(Discount $discount, Order $order): Order
    {
        $refreshedOrder = $order->fresh();
        if (
            in_array($discount->target_type, [DiscountTargetType::ORDER_VALUE, DiscountTargetType::SHIPPING_PRICE])
            && $refreshedOrder?->discounts->count() === 0
        ) {
            $order = $this->roundProductPrices($order);
        }

        return match ($discount->target_type) {
            DiscountTargetType::CHEAPEST_PRODUCT => $this->applyDiscountOnOrderCheapestProduct($order, $discount),
            DiscountTargetType::ORDER_VALUE => $this->applyDiscountOnOrderValue($order, $discount),
            DiscountTargetType::PRODUCTS => $this->applyDiscountOnOrderProducts($order, $discount),
            DiscountTargetType::SHIPPING_PRICE => $this->applyDiscountOnOrderShipping($order, $discount),
        };
    }

    /**
     * @throws StoreException
     * @throws ClientException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws StoreException
     * @throws MoneyMismatchException
     * @throws DtoException
     */
    public function applyAllDiscountsOnProduct(
        Product $product,
        Collection $salesWithBlockList,
        ?Currency $currency = null,
    ): void {
        [
            // @var PriceDto[] $minPricesDiscounted
            $minPricesDiscounted,
            // @var PriceDto[] $maxPricesDiscounted
            $maxPricesDiscounted,
            $productSales,
        ] = $this->calcAllDiscountsOnProduct($product, $salesWithBlockList, false, $currency ?? Currency::DEFAULT);

        // TODO: Remove this; this is a temporary solution fo testing
        if ($currency === null) {
            foreach (Currency::cases() as $case) {
                if ($case !== Currency::DEFAULT) {
                    if ($priceMin = $product->pricesMinInitial()->where('currency', $case->value)->latest()->first()) {
                        $minPricesDiscounted[] = PriceDto::from($priceMin);
                    }
                    if ($priceMax = $product->pricesMaxInitial()->where('currency', $case->value)->latest()->first()) {
                        $maxPricesDiscounted[] = PriceDto::from($priceMax);
                    }
                }
            }
        }

        $this->productRepository->setProductPrices($product->getKey(), [
            ProductPriceType::PRICE_MIN->value => $minPricesDiscounted,
            ProductPriceType::PRICE_MAX->value => $maxPricesDiscounted,
        ]);

        // detach and attach only add 2 queries to database, sync add 1 query for every element in given array,
        $product->sales()->detach();
        $product->sales()->attach($productSales->pluck('id'));
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws ClientException
     */
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

    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    private function applyDiscountOnOrderCheapestProduct(Order $order, Discount $discount): Order
    {
        /** @var OrderProduct $product */
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
                    'vat_rate' => 0, // TODO: add VAT form sales channel
                ]);

                foreach ($product->schemas as $schema) {
                    $newProduct->schemas()->create(
                        $schema->only('name', 'value', 'price_initial', 'price'),
                    );
                }

                $product->discounts->each(fn (Discount $discount) => $this->attachDiscount(
                    $newProduct,
                    $discount,
                    $discount->pivot->applied_discount,
                ));

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
        return match ($discount->target_type) {
            DiscountTargetType::PRODUCTS => $this->applyDiscountOnCartItems($discount, $cartDto, $cart),
            DiscountTargetType::ORDER_VALUE => $this->applyDiscountOnCartTotal($discount, $cart),
            DiscountTargetType::SHIPPING_PRICE => $this->applyDiscountOnCartShipping($discount, $cartDto, $cart),
            DiscountTargetType::CHEAPEST_PRODUCT => $this->applyDiscountOnCartCheapestItem($discount, $cart),
        };
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     */
    private function applyDiscountOnCartShipping(
        Discount $discount,
        CartDto $cartDto,
        CartResource $cartResource
    ): CartResource {
        $shippingPrice = Money::of(
            $cartResource->shipping_price,
            Currency::DEFAULT->value,
        );

        if (
            in_array(
                $cartDto->getShippingMethodId(),
                $discount->shippingMethods->pluck('id')->toArray()
            ) === $discount->target_is_allow_list
        ) {
            // TODO: Change this random assignment
            $cartResource->shipping_price -= $this->calcAppliedDiscount(
                $shippingPrice,
                $this->calc($shippingPrice, $discount),
                'minimal_shipping_price',
            )->getAmount()->toFloat();

            $cartResource->shipping_price = round($cartResource->shipping_price, 2, PHP_ROUND_HALF_UP);
        }

        return $cartResource;
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     */
    private function applyDiscountOnCartTotal(Discount $discount, CartResource $cartResource): CartResource
    {
        $cartTotal = Money::of(
            $cartResource->cart_total,
            Currency::DEFAULT->value,
        );

        // TODO: Change this random assignment
        $cartResource->cart_total -= $this->calcAppliedDiscount(
            $cartTotal,
            $this->calc($cartTotal, $discount),
            'minimal_order_price',
        )->getAmount()->toFloat();
        $cartResource->cart_total = round($cartResource->cart_total, 2, PHP_ROUND_HALF_UP);

        return $cartResource;
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws ClientException
     * @throws NumberFormatException
     */
    private function applyDiscountOnCartItems(Discount $discount, CartDto $cartDto, CartResource $cart): CartResource
    {
        $cartItems = [];
        $cartValue = 0;

        /** @var CartItemDto $item */
        foreach ($cartDto->getItems() as $item) {
            $cartItem = $cart->items->filter(fn ($value, $key) => $value->cartitem_id === $item->getCartItemId())->first();

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

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
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
            $cart->items->first(fn ($value): bool => $value->cartitem_id === $cartItem->cartitem_id && $value->quantity === $cartItem->quantity)->quantity = $cartItem->quantity - 1;

            $cartItem = new CartItemResponse(
                $cartItem->cartitem_id,
                $cartItem->price,
                $cartItem->price_discounted,
                1,
            );
            $cart->items->push($cartItem);
        }

        $price = $cartItem->price_discounted;
        $priceAsMoney = Money::of($price, Currency::DEFAULT->value);

        if ($price !== $minimalProductPrice) {
            $newPrice = round($price - $this->calc($priceAsMoney, $discount)->getAmount()->toFloat(), 2, PHP_ROUND_HALF_UP);

            $cartItem->price_discounted = max($newPrice, $minimalProductPrice);

            $cart->cart_total -= ($price - $cartItem->price_discounted) * $cartItem->quantity;
        }

        return $cart;
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     */
    private function applyDiscountOnOrderValue(Order $order, Discount $discount): Order
    {
        $cartTotal = Money::of(
            $order->cart_total,
            Currency::DEFAULT->value,
        );

        $appliedDiscount = $this->calcAppliedDiscount(
            $cartTotal,
            $this->calc($cartTotal, $discount),
            'minimal_order_price',
        );

        // TODO: Service shouldn't modify prices randomly
        $order->cart_total = $cartTotal->minus($appliedDiscount)->getAmount()->toFloat();

        // TODO: Change to money
        $this->attachDiscount($order, $discount, $appliedDiscount->getAmount()->toFloat());

        return $order;
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     */
    private function applyDiscountOnOrderShipping(Order $order, Discount $discount): Order
    {
        $shipping_price = Money::of(
            $order->shipping_price,
            Currency::DEFAULT->value,
        );

        if (
            in_array(
                $order->shipping_method_id,
                $discount->shippingMethods->pluck('id')->toArray()
            ) === $discount->target_is_allow_list
        ) {
            $appliedDiscount = $this->calcAppliedDiscount(
                $shipping_price,
                $this->calc($shipping_price, $discount),
                'minimal_shipping_price',
            );

            // TODO: Service shouldn't modify prices randomly
            $order->shipping_price = $shipping_price->minus($appliedDiscount)->getAmount()->toFloat();

            // TODO: Change to money
            $this->attachDiscount($order, $discount, $appliedDiscount->getAmount()->toFloat());
        }

        return $order;
    }

    private function attachDiscount(Order|OrderProduct $object, Discount $discount, float $appliedDiscount): void
    {
        $code = $discount->code !== null ? ['code' => $discount->code] : [];

        // TODO: Attach prices with order rework

        $object->discounts()->attach(
            $discount->getKey(),
            [
                'name' => $discount->name,
                'percentage' => $discount->percentage,
                'target_type' => $discount->target_type,
                'applied_discount' => $appliedDiscount,
            ] + $code,
        );
    }

    public function activeSales(): Collection
    {
        $sales = Discount::query()
            ->whereNull('code')
            ->where('active', '=', true)
            ->where('target_type', '=', DiscountTargetType::PRODUCTS->value)
            ->where(
                fn (Builder $query) => $query
                    ->whereHas('conditionGroups', function ($query): void {
                        $query
                            ->whereHas('conditions', function ($query): void {
                                $query->whereIn('type', [ConditionType::DATE_BETWEEN->value, ConditionType::TIME_BETWEEN->value, ConditionType::WEEKDAY_IN->value]);
                            });
                    })
                    ->orWhereDoesntHave('conditionGroups')
            )
            ->with(['conditionGroups', 'conditionGroups.conditions'])
            ->get();

        return $sales->filter(function ($sale): bool {
            foreach ($sale->conditionGroups as $conditionGroup) {
                foreach ($conditionGroup->conditions as $condition) {
                    $result = match ($condition->type) {
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

    /**
     * @throws MoneyMismatchException
     * @throws ServerException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws MathException
     * @throws ClientException
     * @throws NumberFormatException
     */
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

    /**
     * @param array<DiscountTargetType> $targetTypes
     */
    private function getActiveSalesAndCoupons(array|Missing $couponIds, array $targetTypes = []): Collection
    {
        $targetTypesValues = Arr::map($targetTypes, fn (DiscountTargetType $type) => $type->value);

        // Get all active discounts
        $salesQuery = Discount::query()
            ->where('active', '=', true)
            ->whereNull('code')
            ->with([
                'orders',
                'products',
                'productSets',
                'productSets.children',
                'productSets.products',
                'conditionGroups',
                'conditionGroups.conditions',
            ]);
        if (count($targetTypesValues)) {
            $salesQuery = $salesQuery->whereIn('target_type', $targetTypesValues);
        }
        $sales = $salesQuery->get();

        // No coupons used
        if ($couponIds instanceof Missing) {
            return $this->sortDiscounts($sales);
        }

        // Get all active coupons
        $couponsQuery = Discount::query()
            ->where('active', '=', true)
            ->whereIn('code', $couponIds)
            ->with([
                'orders',
                'products',
                'productSets',
                'productSets.children',
                'productSets.products',
                'conditionGroups',
                'conditionGroups.conditions',
            ]);
        if (count($targetTypesValues)) {
            $couponsQuery = $couponsQuery->whereIn('target_type', $targetTypesValues);
        }
        $coupons = $couponsQuery->get();

        // Posortowanie w kolejności do naliczania zniżek
        return $this->sortDiscounts($sales->merge($coupons));
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

    private function checkDiscountTarget(Discount $discount, CartDto $cart): bool
    {
        if ($discount->target_type === DiscountTargetType::PRODUCTS) {
            if ($discount->target_is_allow_list) {
                /** @var CartItemDto $item */
                foreach ($cart->getItems() as $item) {
                    if ($discount->allProductsIds()->contains(fn ($value): bool => $value === $item->getProductId())) {
                        return true;
                    }
                }
            } else {
                /** @var CartItemDto $item */
                foreach ($cart->getItems() as $item) {
                    if ($discount->allProductsIds()->doesntContain(fn ($value): bool => $value === $item->getProductId())) {
                        return true;
                    }
                }
            }
        }

        if ($discount->target_type === DiscountTargetType::SHIPPING_PRICE) {
            if ($discount->target_is_allow_list) {
                return $discount->shippingMethods->contains(fn ($value): bool => $value->getKey() === $cart->getShippingMethodId());
            }

            return $discount->shippingMethods->doesntContain(fn ($value): bool => $value->getKey() === $cart->getShippingMethodId());
        }

        return in_array(
            $discount->target_type,
            [DiscountTargetType::ORDER_VALUE, DiscountTargetType::CHEAPEST_PRODUCT]
        );
    }

    public function checkDiscountHasTimeConditions(Discount $discount): bool
    {
        $conditionsGroups = $discount->conditionGroups;
        foreach ($conditionsGroups as $conditionGroup) {
            foreach ($conditionGroup->conditions as $condition) {
                if (
                    in_array(
                        $condition->type,
                        [
                            ConditionType::DATE_BETWEEN,
                            ConditionType::TIME_BETWEEN,
                            ConditionType::WEEKDAY_IN,
                        ]
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function checkDiscountTimeConditions(Discount $discount): bool
    {
        /** @var ConditionGroup $conditionGroup */
        foreach ($discount->conditionGroups as $conditionGroup) {
            /** @var DiscountCondition $condition */
            foreach ($conditionGroup->conditions as $condition) {
                $result = match ($condition->type) {
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

    /**
     * Checked conditions.
     */

    /**
     * Check if product have any valid condition group.
     *
     * @throws ServerException
     */
    private function checkConditionGroupsForProduct(Discount $discount, bool $checkForCurrentUser): bool
    {
        // return true if there is no condition groups
        if ($discount->conditionGroups->count() <= 0) {
            return true;
        }

        foreach ($discount->conditionGroups as $conditionGroup) {
            // return true if any condition group is valid
            if ($this->checkConditionGroupForProduct($conditionGroup, $checkForCurrentUser)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if given condition group is valid.
     *
     * @throws ServerException
     */
    private function checkConditionGroupForProduct(ConditionGroup $group, bool $checkForCurrentUser): bool
    {
        foreach ($group->conditions as $condition) {
            // return false if any condition is not valid
            if (!$this->checkConditionForProduct($condition, $checkForCurrentUser)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if given condition is valid for product feed.
     *
     * This executes for price cache on products
     *
     * It should ignore current active user and calc general price for everyone
     *
     * @throws ServerException
     */
    private function checkConditionForProduct(DiscountCondition $condition, bool $checkForCurrentUser): bool
    {
        return match ($condition->type) {
            // ignore discount dependant on cart state
            ConditionType::ORDER_VALUE => false,
            // ignore discount dependant on cart state
            ConditionType::PRODUCT_IN_SET => false,
            // ignore discount dependant on cart state
            ConditionType::PRODUCT_IN => false,
            ConditionType::USER_IN_ROLE => $checkForCurrentUser && $this->checkConditionUserInRole($condition),
            ConditionType::USER_IN => $checkForCurrentUser && $this->checkConditionUserIn($condition),
            ConditionType::DATE_BETWEEN => $this->checkConditionDateBetween($condition),
            ConditionType::TIME_BETWEEN => $this->checkConditionTimeBetween($condition),
            ConditionType::MAX_USES => $this->checkConditionMaxUses($condition),
            ConditionType::MAX_USES_PER_USER => $checkForCurrentUser && $this->checkConditionMaxUsesPerUser($condition),
            ConditionType::WEEKDAY_IN => $this->checkConditionWeekdayIn($condition),
            ConditionType::CART_LENGTH => false,
            // For product price cache assume no promo codes used
            ConditionType::COUPONS_COUNT => $this->checkConditionCouponsCount($condition, 0),
        };
    }

    /**
     * This executes for orders and cart.
     *
     * It needs to account for current session user and calculate personalized price
     */
    public function checkCondition(
        DiscountCondition $condition,
        ?CartOrderDto $dto = null,
        float $cartValue = 0,
    ): bool {
        return match ($condition->type) {
            ConditionType::CART_LENGTH => $this->checkConditionCartLength($condition, $dto?->getCartLength() ?? 0),
            ConditionType::COUPONS_COUNT => $this->checkConditionCouponsCount($condition, is_array($dto?->getCoupons()) ? count($dto->getCoupons()) : 0),
            ConditionType::DATE_BETWEEN => $this->checkConditionDateBetween($condition),
            ConditionType::MAX_USES => $this->checkConditionMaxUses($condition),
            ConditionType::MAX_USES_PER_USER => $this->checkConditionMaxUsesPerUser($condition),
            ConditionType::ORDER_VALUE => $this->checkConditionOrderValue($condition, $cartValue),
            ConditionType::PRODUCT_IN => $this->checkConditionProductIn($condition, $dto?->getProductIds() ?? []),
            ConditionType::PRODUCT_IN_SET => $this->checkConditionProductInSet($condition, $dto?->getProductIds() ?? []),
            ConditionType::TIME_BETWEEN => $this->checkConditionTimeBetween($condition),
            ConditionType::USER_IN => $this->checkConditionUserIn($condition),
            ConditionType::USER_IN_ROLE => $this->checkConditionUserInRole($condition),
            ConditionType::WEEKDAY_IN => $this->checkConditionWeekdayIn($condition),
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
        if (!$discount->active) {
            return false;
        }

        if ($discount->conditionGroups->isEmpty()) {
            return true;
        }

        foreach ($discount->conditionGroups as $conditionGroup) {
            if ($this->checkConditionGroup($conditionGroup, $dto, $cartValue)) {
                return true;
            }
        }

        return false;
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
        /** @var Product $product */
        $product = Product::query()->where('id', $productId)->firstOrFail();

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
        $conditionGroup = ConditionGroup::query()->create();

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
        /** @var User|App|null $user */
        $user = Auth::user();

        if ($user instanceof User) {
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
        $productSets = ProductSet::query()->whereIn('id', $conditionDto->getProductSets())->get();

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
            ? Str::before($startAt, 'T') . 'T00:00:00' : $startAt;

        $endAt = !$endAt instanceof Missing && !Str::contains($endAt, ':')
            ? Str::before($endAt, 'T') . 'T23:59:59' : $endAt;

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

    private function checkConditionCartLength(DiscountCondition $condition, float|int $cartLength): bool
    {
        $conditionDto = CartLengthConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        return $this->checkConditionLength($conditionDto, $cartLength);
    }

    private function checkConditionCouponsCount(DiscountCondition $condition, float|int $couponsCount): bool
    {
        $conditionDto = CouponsCountConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        return $this->checkConditionLength($conditionDto, $couponsCount);
    }

    private function checkConditionLength(
        CartLengthConditionDto|CouponsCountConditionDto $conditionDto,
        float|int $count,
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
}
