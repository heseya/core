<?php

declare(strict_types=1);

namespace App\Services;

use App\Dtos\CartDto;
use App\Dtos\CartItemDto;
use App\Dtos\CartLengthConditionDto;
use App\Dtos\CartOrderDto;
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
use App\Dtos\UserInOrganizationConditionDto;
use App\Dtos\UserInRoleConditionDto;
use App\Dtos\WeekDayInConditionDto;
use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Events\CouponCreated;
use App\Events\CouponDeleted;
use App\Events\CouponUpdated;
use App\Events\ProductPriceUpdated;
use App\Events\SaleCreated;
use App\Events\SaleDeleted;
use App\Events\SaleUpdated;
use App\Exceptions\ClientException;
use App\Exceptions\ServerException;
use App\Exceptions\StoreException;
use App\Jobs\CalculateDiscount;
use App\Models\App;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Repositories\DiscountRepository;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\ShippingTimeDateServiceContract;
use App\Traits\GetPublishedLanguageFilter;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Order\Dtos\OrderPriceDto;
use Domain\Order\Resources\CartItemResource;
use Domain\Order\Resources\CartResource;
use Domain\Order\Resources\CouponShortResource;
use Domain\Order\Resources\SalesShortResource;
use Domain\Organization\Models\Organization;
use Domain\Price\Dtos\ProductCachedPriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\Price\Enums\ProductPriceType;
use Domain\Price\PriceRepository;
use Domain\Price\PriceService;
use Domain\Price\Resources\ProductCachedPriceData;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapService;
use Domain\ProductSchema\Models\Schema;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelService;
use Domain\Seo\SeoMetadataService;
use Domain\Setting\Services\Contracts\SettingsServiceContract;
use Domain\ShippingMethod\Models\ShippingMethod;
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
use Illuminate\Support\Str;

readonly class DiscountService
{
    use GetPublishedLanguageFilter;

    public function __construct(
        private DiscountRepository $discountRepository,
        private MetadataServiceContract $metadataService,
        private PriceRepository $priceRepository,
        private PriceService $priceService,
        private SalesChannelService $salesChannelService,
        private SeoMetadataService $seoMetadataService,
        private SettingsServiceContract $settingsService,
        private ShippingTimeDateServiceContract $shippingTimeDateService,
        private PriceMapService $priceMapService,
    ) {}

    public function index(CouponIndexDto|SaleIndexDto $dto): LengthAwarePaginator
    {
        return Discount::searchByCriteria($dto->toArray() + $this->getPublishedLanguageFilter('discounts'))
            ->orderBy('updated_at', 'DESC')
            ->with(['metadata', 'metadataPrivate', 'amounts'])
            ->withOrdersCount()
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
        $discount = Discount::query()->make($dto->toArray());

        foreach ($dto->translations as $lang => $translation) {
            $discount->setLocale($lang)->fill($translation);
        }
        $discount->save();

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

        if ($dto->getSeo() !== null && !($dto->getSeo() instanceof Missing)) {
            $this->seoMetadataService->createOrUpdateFor($discount, $dto->getSeo());
        }

        if (!($dto->amounts instanceof Missing)) {
            $this->discountRepository->setDiscountAmounts($discount->getKey(), $dto->amounts);
        }
        $discount->refresh();

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

        foreach ($dto->translations as $lang => $translation) {
            $discount->setLocale($lang)->fill($translation);
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

        if ($dto->getSeo() !== null && !($dto->getSeo() instanceof Missing)) {
            $this->seoMetadataService->createOrUpdateFor($discount, $dto->getSeo());
        } elseif ($dto->getSeo() === null && $discount->seo) {
            $this->seoMetadataService->delete($discount->seo);
        }
        $discount->refresh();

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

        $percentage = $discount->order_discount?->percentage ?? $discount->percentage;

        if ($percentage !== null) {
            $percentage = BigDecimal::of($percentage)->dividedBy(100, roundingMode: RoundingMode::HALF_DOWN);

            // It's debatable which rounding mode we use based on context
            // This fits with current tests
            $value = $value->multipliedBy($percentage, RoundingMode::HALF_DOWN);
        } else {
            [$amount] = $this->discountRepository->getDiscountAmounts($discount->getKey(), $currency);
            $value = $amount->value;
        }

        return $value;
    }

    /**
     * @param Collection<int,SalesChannel>|null $salesChannels
     *
     * @throws ServerException
     * @throws MoneyMismatchException
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws ClientException
     * @throws NumberFormatException
     * @throws StoreException
     */
    public function calculateDiscount(Discount $discount, ?Collection $salesChannels = null): void
    {
        // If discount has conditions based on time, then must be added or removed from cache
        $this->checkIsDiscountActive($discount);

        // Why do I need this
        $salesWithBlockList = $this->getSalesWithBlockList();
        $products = Collection::make();

        // if job is called after update, then calculate discount for all products,
        // because it may change the list of related products or target_is_allow_list value
        if ($discount->wasRecentlyCreated && $discount->active) {
            $products = $this->allDiscountProductsIds($discount);
        }

        $this->applyDiscountsOnProductsLazy($products, $salesWithBlockList, $salesChannels);
    }

    /**
     * @throws ClientException
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws ServerException
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
     * @throws ClientException
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws ServerException
     */
    public function calcOrderShippingDiscounts(Order $order, OrderDto $orderDto): Order
    {
        return $this->calcOrderDiscounts($order, $orderDto, [
            DiscountTargetType::SHIPPING_PRICE,
        ]);
    }

    /**
     * Calculate prices and discounts in Cart
     * All discounts are applied to Net prices.
     *
     * @param Collection<int,Product> $products
     *
     * @throws MathException
     * @throws StoreException
     * @throws MoneyMismatchException
     * @throws UnknownCurrencyException
     * @throws DtoException
     */
    public function calcCartDiscounts(CartDto $cart, Collection $products, BigDecimal $vat_rate): CartResource
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

        $salesChannel = SalesChannel::find($cart->sales_channel_id);
        $priceMap = $salesChannel?->priceMap;

        if ($priceMap) {
            $currency = $priceMap->currency;
        } else {
            $currency = $cart->currency;
            $priceMap = PriceMap::findOrFail($currency->getDefaultPriceMapId());
        }

        assert($priceMap instanceof PriceMap);

        $cartItems = [];
        $cartValue = Money::zero($currency->value);

        foreach ($cart->getItems() as $cartItem) {
            $product = $products->firstWhere('id', $cartItem->getProductId());

            if (!($product instanceof Product)) {
                // skip when product is not available
                continue;
            }

            $price = $this->priceMapService->getOrCreateMappedPriceForPriceMap($product, $priceMap)->value;

            foreach ($cartItem->getSchemas() as $schemaId => $value) {
                /** @var Schema $schema */
                $schema = $product->schemas()->findOrFail($schemaId);

                $option_price = $schema->getPrice($value, $cartItem->getSchemas(), $priceMap);

                $price = $price->plus($option_price);
            }
            $cartValue = $cartValue->plus($price->multipliedBy($cartItem->getQuantity()));

            $cartItems[] = new CartItemResource(
                $cartItem->getCartItemId(),
                OrderPriceDto::from($price, $vat_rate, $priceMap->is_net),
                OrderPriceDto::from($price, $vat_rate, $priceMap->is_net),
                Currency::from($price->getCurrency()->getCurrencyCode()),
                $cartItem->getQuantity(),
            );
        }

        $cartShippingTimeAndDate = $this->shippingTimeDateService->getTimeAndDateForCart($cart, $products);

        $shippingPrice = Money::zero($currency->value);

        $summary = $cartValue;

        $cartResource = new CartResource(
            CartItemResource::collection($cartItems),
            CouponShortResource::collection([]),
            SalesShortResource::collection([]),
            OrderPriceDto::from($cartValue, $vat_rate, $priceMap->is_net),
            OrderPriceDto::from($cartValue, $vat_rate, $priceMap->is_net),
            OrderPriceDto::from($shippingPrice, $vat_rate, $priceMap->is_net),
            OrderPriceDto::from($shippingPrice, $vat_rate, $priceMap->is_net),
            OrderPriceDto::from($summary, $vat_rate, $priceMap->is_net),
            $currency,
            $cartShippingTimeAndDate['shipping_time'] ?? null,
            $cartShippingTimeAndDate['shipping_date'] ?? null,
        );

        if ($cartResource->items->toCollection()->isEmpty()) {
            return $cartResource;
        }

        foreach ($discounts as $discount) {
            if (
                $this->checkDiscountTarget($discount, $cart)
                && $this->checkConditionGroups($discount, $cart, $cartResource->cart_total->net)
            ) {
                $cartResource = $this->applyDiscountOnCart($discount, $cart, $cartResource, $priceMap);
                if ($priceMap->is_net) {
                    $newSummary = $cartResource->cart_total->net->plus($cartResource->shipping_price->net);
                } else {
                    $newSummary = $cartResource->cart_total->gross->plus($cartResource->shipping_price->gross);
                }
                $appliedDiscount = $summary->minus($newSummary);

                $cartResource = $this->addDiscountToCartResource(OrderPriceDto::from($appliedDiscount, $vat_rate, $priceMap->is_net), $discount, $cartResource, $priceMap);

                $summary = $newSummary;
            }
        }

        if ($priceMap->is_net) {
            $summaryForShipping = $this->salesChannelService->addVat($summary, $vat_rate);
        } else {
            $summaryForShipping = $summary;
        }

        $shippingPrice = Money::zero($currency->value);
        if ($shippingMethod !== null) {
            $shippingPrice = $shippingPrice->plus($shippingMethod->getPrice($summaryForShipping));
        }
        if ($shippingMethodDigital !== null) {
            $shippingPrice = $shippingPrice->plus($shippingMethodDigital->getPrice($summaryForShipping));
        }

        if ($priceMap->is_net) {
            $shippingPrice = $this->salesChannelService->removeVat($shippingPrice, $vat_rate);
        }

        $cartResource->summary = OrderPriceDto::from($cartValue->plus($shippingPrice), $vat_rate, $priceMap->is_net);
        $cartResource->shipping_price_initial = OrderPriceDto::from($shippingPrice, $vat_rate, $priceMap->is_net);
        $cartResource->shipping_price = OrderPriceDto::from($shippingPrice, $vat_rate, $priceMap->is_net);

        foreach ($discounts->filter(fn ($discount) => $discount->target_type === DiscountTargetType::SHIPPING_PRICE) as $discount) {
            if (
                $this->checkShippingPriceTarget($discount, $cart)
                && $this->checkConditionGroups($discount, $cart, $cartResource->cart_total->net)
            ) {
                $oldShipping = $cartResource->shipping_price;
                $cartResource = $this->applyDiscountOnCart($discount, $cart, $cartResource, $priceMap);

                if ($priceMap->is_net) {
                    $appliedDiscount = $oldShipping->net->minus($cartResource->shipping_price->net);
                } else {
                    $appliedDiscount = $oldShipping->gross->minus($cartResource->shipping_price->gross);
                }
                $cartResource = $this->addDiscountToCartResource(OrderPriceDto::from($appliedDiscount, $oldShipping->vat_rate, $priceMap->is_net), $discount, $cartResource, $priceMap);
            }
        }

        if ($priceMap->is_net) {
            $cartResource->summary = OrderPriceDto::from($cartResource->cart_total->net->plus($cartResource->shipping_price->net), $vat_rate, $priceMap->is_net);
        } else {
            $cartResource->summary = OrderPriceDto::from($cartResource->cart_total->gross->plus($cartResource->shipping_price->gross), $vat_rate, $priceMap->is_net);
        }

        return $cartResource;
    }

    private function checkShippingPriceTarget(Discount $discount, CartDto $cart): bool
    {
        if ($discount->target_type === DiscountTargetType::SHIPPING_PRICE) {
            if ($discount->target_is_allow_list) {
                return $discount->shippingMethods->contains(
                    fn ($value): bool => $value->getKey() === $cart->getShippingMethodId(),
                );
            }

            return $discount->shippingMethods->doesntContain(
                fn ($value): bool => $value->getKey() === $cart->getShippingMethodId(),
            );
        }

        return false;
    }

    /**
     * @param Collection<int,Product> $products
     *
     * @return Collection<int,ProductCachedPriceData>
     */
    public function calcProductsListDiscounts(Collection $products, SalesChannel $salesChannel): Collection
    {
        $salesWithBlockList = $this->getSalesWithBlockList();

        return $products->map(function (Product $product) use ($salesWithBlockList, $salesChannel) {
            $sales = $this->getAllAplicableSalesForProduct($product, $salesWithBlockList, true);

            $minPrice = $this->calcAllDiscountsOnProduct(
                $product,
                $sales,
                $salesChannel,
            );

            return ProductCachedPriceData::fromProductCachedPriceDto(
                $product->getKey(),
                $minPrice,
            );
        })->collect();
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
        $minimal = $this->settingsService->getMinimalPrice($setting);
        if (in_array($setting, ['minimal_product_price', 'minimal_order_price']) && $minimal <= 0) {
            $minimal = 0.01;
        }
        $minimalPrice = Money::of($minimal, $price->getCurrency());

        $maximumDiscount = $price->minus($minimalPrice);

        return $appliedDiscount->isGreaterThan($maximumDiscount) ? $maximumDiscount : $appliedDiscount;
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
        Discount $discount,
        Currency|PriceMap $priceMap,
    ): OrderProduct {
        $priceMap = $priceMap instanceof Currency ? PriceMap::findOrFail($priceMap->getDefaultPriceMapId()) : $priceMap;

        $price = $this->priceMapService->getOrCreateMappedPriceForPriceMap($product, $priceMap)->value;

        foreach ($orderProductDto->getSchemas() as $schemaId => $value) {
            /** @var Schema $schema */
            $schema = $product->schemas()->findOrFail($schemaId);

            $price = $price->plus($schema->getPrice($value, $orderProductDto->getSchemas(), $priceMap));
        }

        $calculatedPrice = $this->calcPrice(OrderPriceDto::from($price, 0, $priceMap->is_net), $product->getKey(), $discount, $priceMap);

        return new OrderProduct([
            'product_id' => $product->getKey(),
            'quantity' => $orderProductDto->getQuantity(),
            'price' => $calculatedPrice->net,
        ]);
    }

    /**
     * @param Collection<int,Product> $products
     * @param Collection<int,SalesChannel> $salesChannels
     *
     * @throws MoneyMismatchException
     * @throws ServerException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws ClientException
     * @throws NumberFormatException
     * @throws DtoException
     * @throws StoreException
     */
    public function applyDiscountsOnProducts(Collection $products, ?Collection $salesChannels = null): void
    {
        $salesWithBlockList = $this->getSalesWithBlockList();
        foreach ($products as $product) {
            $this->applyAllDiscountsOnProduct($product, $salesWithBlockList, $salesChannels);
        }
    }

    /**
     * @param Collection<int,SalesChannel>|null $salesChannels
     *
     * @throws ServerException
     * @throws MoneyMismatchException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws ClientException
     * @throws NumberFormatException
     * @throws DtoException
     * @throws StoreException
     */
    public function applyDiscountsOnProduct(Product $product, ?Collection $salesChannels = null): void
    {
        $salesWithBlockList = $this->getSalesWithBlockList();
        $this->applyAllDiscountsOnProduct($product, $salesWithBlockList, $salesChannels);
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     * @throws ServerException
     */
    public function applyDiscountOnOrderProduct(OrderProduct $orderProduct, Discount $discount): OrderProduct
    {
        $minimalProductPrice = Money::ofMinor(1, $orderProduct->currency->value);
        $price = $orderProduct->price;

        if (
            !$price->isEqualTo($minimalProductPrice)
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
     * @throws ServerException
     */
    public function applyDiscountOnCartItem(
        Discount $discount,
        CartItemDto $cartItem,
        CartResource $cart,
        PriceMap $priceMap,
    ): CartItemResource {
        /** @var CartItemResource $result */
        $result = $cart->items->filter(
            fn (CartItemResource $value) => $value->cartitem_id === $cartItem->getCartItemId(),
        )->first();

        $result->price_discounted = OrderPriceDto::from(
            $this->calcPrice(
                $result->price_discounted,
                $cartItem->getProductId(),
                $discount,
                $priceMap,
            ),
            $result->price_discounted->vat_rate,
            $priceMap->is_net,
        );

        return $result;
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws ServerException
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
     * @param Collection<int,Discount> $salesWithBlockList
     * @param Collection<int,SalesChannel>|null $salesChannels
     *
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
        ?Collection $salesChannels = null,
    ): void {
        if ($salesChannels === null) {
            $salesChannels = $this->salesChannelService->getCachedActiveSalesChannels();
        }

        $allOldPrices = [];
        $allNewPrices = [];

        $sales = $this->getAllAplicableSalesForProduct($product, $salesWithBlockList);

        foreach ($salesChannels as $salesChannel) {
            try {
                $oldPrices = $this->priceService->getCachedProductPrices($product, [
                    ProductPriceType::PRICE_MIN,
                ], $salesChannel);

                $oldPrice = $oldPrices->get(ProductPriceType::PRICE_MIN->value, collect())->first();
            } catch (ServerException $ex) {
                $oldPrice = null;
            }

            $newPrice = $this->calcAllDiscountsOnProduct($product, $sales, $salesChannel);

            if ($oldPrice === null || !$oldPrice->net->isEqualTo($newPrice->net) || !$oldPrice->gross->isEqualTo($newPrice->gross)) {
                $allNewPrices[] = $newPrice;
            }
        }

        $this->priceService->setCachedProductPrices($product, [
            ProductPriceType::PRICE_MIN->value => $allNewPrices,
        ]);

        $product->sales()->detach();
        $product->sales()->attach($sales->pluck('id'));

        ProductPriceUpdated::dispatchIf(
            !empty($allNewPrices),
            $product->getKey(),
            $allOldPrices,
            $allNewPrices,
        );
    }

    /**
     * @param Collection<int,Discount> $salesWithBlockList
     *
     * @return Collection<int,Discount>
     */
    public function getAllAplicableSalesForProduct(Product $product, Collection $salesWithBlockList, bool $calcForCurrentUser = false): Collection
    {
        return $this->sortDiscounts($product->allProductSales($salesWithBlockList))->filter(fn ($sale) => $this->checkConditionGroupsForProduct($sale, $calcForCurrentUser));
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
                                $query->whereIn(
                                    'type',
                                    [
                                        ConditionType::DATE_BETWEEN->value,
                                        ConditionType::TIME_BETWEEN->value,
                                        ConditionType::WEEKDAY_IN->value,
                                    ],
                                );
                            });
                    })
                    ->orWhereDoesntHave('conditionGroups'),
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
     * @throws StoreException
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
                        ],
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
     * This executes for orders and cart.
     *
     * It needs to account for current session user and calculate personalized price
     */
    public function checkCondition(
        DiscountCondition $condition,
        Money $cartValue,
        ?CartOrderDto $dto = null,
    ): bool {
        return match ($condition->type) {
            ConditionType::CART_LENGTH => $this->checkConditionCartLength($condition, $dto?->getCartLength() ?? 0),
            ConditionType::COUPONS_COUNT => $this->checkConditionCouponsCount(
                $condition,
                is_array($dto?->getCoupons()) ? count($dto->getCoupons()) : 0,
            ),
            ConditionType::DATE_BETWEEN => $this->checkConditionDateBetween($condition),
            ConditionType::MAX_USES => $this->checkConditionMaxUses($condition),
            ConditionType::MAX_USES_PER_USER => $this->checkConditionMaxUsesPerUser($condition),
            ConditionType::ORDER_VALUE => $this->checkConditionOrderValue($condition, $cartValue),
            ConditionType::PRODUCT_IN => $this->checkConditionProductIn($condition, $dto?->getProductIds() ?? []),
            ConditionType::PRODUCT_IN_SET => $this->checkConditionProductInSet(
                $condition,
                $dto?->getProductIds() ?? [],
            ),
            ConditionType::TIME_BETWEEN => $this->checkConditionTimeBetween($condition),
            ConditionType::USER_IN => $this->checkConditionUserIn($condition),
            ConditionType::USER_IN_ROLE => $this->checkConditionUserInRole($condition),
            ConditionType::WEEKDAY_IN => $this->checkConditionWeekdayIn($condition),
            ConditionType::USER_IN_ORGANIZATION => $this->checkConditionUserInOrganization($condition),
        };
    }

    public function checkConditionGroup(
        ConditionGroup $group,
        CartOrderDto $dto,
        Money $cartValue,
    ): bool {
        foreach ($group->conditions as $condition) {
            if (!$this->checkCondition($condition, $cartValue, $dto)) {
                return false;
            }
        }

        return true;
    }

    public function checkConditionGroups(
        Discount $discount,
        CartOrderDto $dto,
        Money $cartValue,
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

            if (method_exists($condition, 'getOrganizations')) {
                $discountCondition->organizations()->attach($condition->getOrganizations());
            }

            if (method_exists($condition, 'getMinValues')) {
                if ($condition->getMinValues() instanceof Missing) {
                    if ($discountCondition->exists) {
                        $discountCondition->pricesMin()->delete();
                    }
                } else {
                    $this->priceRepository->setDiscountConditionPrices($discountCondition, [
                        DiscountConditionPriceType::PRICE_MIN->value => $condition->getMinValues(),
                    ]);
                }
            }
            if (method_exists($condition, 'getMaxValues')) {
                if ($condition->getMaxValues() instanceof Missing) {
                    if ($discountCondition->exists) {
                        $discountCondition->pricesMax()->delete();
                    }
                } else {
                    $this->priceRepository->setDiscountConditionPrices($discountCondition, [
                        DiscountConditionPriceType::PRICE_MAX->value => $condition->getMaxValues(),
                    ]);
                }
            }
        }

        return $conditionGroup;
    }

    public function getSalesWithBlockList(): Collection
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
    private function calcPrice(OrderPriceDto $dto, string $productId, Discount $discount, PriceMap $priceMap): OrderPriceDto
    {
        $minimalProductPrice = Money::ofMinor(1, $dto->currency->value);
        if (!$priceMap->is_net) {
            $minimalProductPrice = $this->salesChannelService->addVat($minimalProductPrice, $dto->vat_rate);
        }

        if (!$dto->net->isEqualTo($minimalProductPrice) && $this->checkIsProductInDiscount($productId, $discount)) {
            $discountAmount = $this->calc($dto->net, $discount);

            if ($priceMap->is_net) {
                $price = $dto->net->minus($discountAmount);
            } else {
                $price = $dto->gross->minus($this->salesChannelService->addVat($discountAmount, $dto->vat_rate));
            }
            $price = Money::max($price, $minimalProductPrice);

            $dto = OrderPriceDto::from($price, $dto->vat_rate, $priceMap->is_net);
        }

        return $dto;
    }

    /**
     * @param Collection<int,Discount> $sales
     * @param array<string,string> $schemas
     *
     * @throws MoneyMismatchException
     * @throws ServerException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     * @throws DtoException
     */
    public function calcAllDiscountsOnProductVariant(
        Product $product,
        Collection $sales,
        SalesChannel $salesChannel,
        array $schemas = [],
    ): ProductCachedPriceDto {
        $priceMap = $salesChannel->priceMap;
        assert($priceMap instanceof PriceMap);
        $minimalProductPrice = Money::ofMinor(1, $priceMap->currency->value);

        $initialPrices = $this->priceService->getCachedProductPrices(
            $product,
            [ProductPriceType::PRICE_MIN_INITIAL],
            $salesChannel,
            false,
        );
        $initialPrice = $initialPrices->get(ProductPriceType::PRICE_MIN_INITIAL->value, collect())->first();

        if ($initialPrice === null) {
            $basePrice = $this->priceMapService->getOrCreateMappedPriceForPriceMap($product, $priceMap);
            $net = $priceMap->is_net ? $basePrice->value : $this->salesChannelService->removeVat($basePrice->value, $this->salesChannelService->getVatRate($salesChannel));
        } else {
            $net = $initialPrice->net;
        }

        foreach ($schemas as $schemaId => $optionId) {
            /** @var Schema $schema */
            $schema = $product->schemas()->findOrFail($schemaId);

            $schema_price = $schema->getPrice($optionId, $schemas, $priceMap);
            if (!$priceMap->is_net) {
                $schema_price = $this->salesChannelService->removeVat($schema_price, $this->salesChannelService->getVatRate($salesChannel));
            }

            $net = $net->plus($schema_price);
        }

        $minPrice = $net;
        foreach ($sales as $sale) {
            if ($minPrice->isGreaterThan($minimalProductPrice)) {
                $minPrice = $this->calcProductPriceDiscount($sale, $minPrice, $minimalProductPrice);
            }
        }

        return ProductCachedPriceDto::from([
            'net' => $minPrice,
            'gross' => $this->salesChannelService->addVat($minPrice, $this->salesChannelService->getVatRate($salesChannel)),
            'currency' => $priceMap->currency,
            'sales_channel_id' => $salesChannel->id,
        ]);
    }

    /**
     * @param Collection<int,Discount> $sales
     *
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
        Collection $sales,
        SalesChannel $salesChannel,
    ): ProductCachedPriceDto {
        return $this->calcAllDiscountsOnProductVariant($product, $sales, $salesChannel);
    }

    /**
     * @param array<int,DiscountTargetType> $targetTypes
     *
     * @throws ClientException
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws ServerException
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

        $order->summary = $order->cart_total->plus($order->shipping_price);
        $order->paid = $order->summary->isLessThanOrEqualTo(0);

        return $order;
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    private function roundProductPrices(Order $order): Order
    {
        // If cheapest product has been split, it will not be returned by $order->products,
        // and $order->products()->get() has products without discount, if order will be saved at this moment,
        // all products in database should be updated, and split product will be returned by $order->products
        $order->push();
        $order->refresh();
        $totalPrice = Money::zero($order->currency->value);
        foreach ($order->products as $product) {
            $totalPrice = $totalPrice->plus($product->price->multipliedBy($product->quantity));
        }

        $order->cart_total = $totalPrice;

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
        $appliedDiscount = $this->calcAppliedDiscount(
            $orderProduct->price,
            $this->calc($orderProduct->price, $discount),
            'minimal_product_price',
        );

        $orderProduct->price = $orderProduct->price->minus($appliedDiscount);

        // Adding a discount to orderProduct
        $this->attachDiscount($orderProduct, $discount, $appliedDiscount);
    }

    /**
     * @param Collection<array-key,Discount> $discounts
     *
     * @return Collection<array-key,Discount>
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
     * @param Collection<int,string> $productIds
     * @param Collection<int,Discount> $salesWithBlockList
     * @param Collection<int,SalesChannel>|null $salesChannels
     *
     * @throws MoneyMismatchException
     * @throws ServerException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws MathException
     * @throws ClientException
     * @throws NumberFormatException
     * @throws StoreException
     */
    public function applyDiscountsOnProductsLazy(Collection $productIds, Collection $salesWithBlockList, ?Collection $salesChannels = null): void
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

        $productQuery->chunk(100, fn (Collection $products) => $products->each(fn (Product $product) => $this->applyAllDiscountsOnProduct($product, $salesWithBlockList, $salesChannels)));
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     * @throws ServerException
     */
    private function applyDiscountOnOrderProducts(Order $order, Discount $discount): Order
    {
        $cartValue = Money::zero($order->currency->value);

        /** @var OrderProduct $product */
        foreach ($order->products as $product) {
            $product = $this->applyDiscountOnOrderProduct($product, $discount);
            $cartValue = $cartValue->plus($product->price->multipliedBy($product->quantity));
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
     * @throws ServerException
     */
    private function applyDiscountOnOrderCheapestProduct(Order $order, Discount $discount): Order
    {
        /** @var OrderProduct $product */
        $product = $order->products->sortBy([
            ['price', 'asc'],
            ['quantity', 'asc'],
        ])->first();

        if ($product !== null) {
            $minimalProductPrice = Money::ofMinor(1, $order->currency->value);

            if ($product->quantity > 1 && !$product->price->isEqualTo($minimalProductPrice)) {
                $product->update(['quantity' => $product->quantity - 1]);

                /** @var OrderProduct $newProduct */
                $newProduct = $order->products()->create([
                    'product_id' => $product->product_id,
                    'quantity' => 1,
                    'name' => $product->name,
                    'price' => $product->price,
                    'price_initial' => $product->price_initial,
                    'base_price' => $product->price,
                    'base_price_initial' => $product->price,
                    'vat_rate' => $product->vat_rate,
                ]);

                foreach ($product->schemas as $schema) {
                    $newProduct->schemas()->create(
                        $schema->only('name', 'value', 'price_initial', 'price'),
                    );
                }

                $product->discounts
                    ->where(fn (Discount $discount) => $discount->order_discount?->applied !== null)
                    ->each(
                        fn (Discount $discount) => $this->attachDiscount(
                            $newProduct,
                            $discount,
                            $discount->order_discount->applied, // @phpstan-ignore-line
                        ),
                    );

                $product = $newProduct;
            }

            $price = $product->price;

            if (!$price->isEqualTo($minimalProductPrice)) {
                $this->calcOrderProductDiscount($product, $discount);
                $product->save();
            }

            $order->cart_total = $order->cart_total->minus(
                $price->minus($product->price)->multipliedBy($product->quantity),
            );
        }

        return $order;
    }

    private function applyDiscountOnCart(Discount $discount, CartDto $cartDto, CartResource $cart, PriceMap $priceMap): CartResource
    {
        return match ($discount->target_type) {
            DiscountTargetType::PRODUCTS => $this->applyDiscountOnCartItems($discount, $cartDto, $cart, $priceMap),
            DiscountTargetType::ORDER_VALUE => $this->applyDiscountOnCartTotal($discount, $cart, $priceMap),
            DiscountTargetType::SHIPPING_PRICE => $this->applyDiscountOnCartShipping($discount, $cartDto, $cart, $priceMap),
            DiscountTargetType::CHEAPEST_PRODUCT => $this->applyDiscountOnCartCheapestItem($discount, $cart, $priceMap),
        };
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     * @throws ServerException
     */
    private function applyDiscountOnCartShipping(
        Discount $discount,
        CartDto $cartDto,
        CartResource $cartResource,
        PriceMap $priceMap,
    ): CartResource {
        if (
            in_array(
                $cartDto->getShippingMethodId(),
                $discount->shippingMethods->pluck('id')->toArray(),
            ) === $discount->target_is_allow_list
        ) {
            $appliedDiscount = $this->calcAppliedDiscount(
                $cartResource->shipping_price->net,
                $this->calc($cartResource->shipping_price->net, $discount),
                'minimal_shipping_price',
            );

            if ($priceMap->is_net) {
                $newShippingPrice = $cartResource->shipping_price->net->minus($appliedDiscount);
            } else {
                $appliedDiscount = $this->salesChannelService->addVat($appliedDiscount, $cartResource->shipping_price->vat_rate);
                $newShippingPrice = $cartResource->shipping_price->gross->minus($appliedDiscount);
            }

            $cartResource->shipping_price = OrderPriceDto::from(
                $newShippingPrice,
                $cartResource->shipping_price->vat_rate,
                $priceMap->is_net,
            );
        }

        return $cartResource;
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     * @throws ServerException
     */
    private function applyDiscountOnCartTotal(Discount $discount, CartResource $cartResource, PriceMap $priceMap): CartResource
    {
        $appliedDiscount = $this->calcAppliedDiscount(
            $cartResource->cart_total->net,
            $this->calc($cartResource->cart_total->net, $discount),
            'minimal_order_price',
        );

        if ($priceMap->is_net) {
            $newCartTotal = $cartResource->cart_total->net->minus($appliedDiscount);
        } else {
            $appliedDiscount = $this->salesChannelService->addVat($appliedDiscount, $cartResource->cart_total->vat_rate);
            $newCartTotal = $cartResource->cart_total->gross->minus($appliedDiscount);
        }

        $cartResource->cart_total = OrderPriceDto::from(
            $newCartTotal,
            $cartResource->cart_total->vat_rate,
            $priceMap->is_net,
        );

        return $cartResource;
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws ServerException
     */
    private function applyDiscountOnCartItems(Discount $discount, CartDto $cartDto, CartResource $cart, PriceMap $priceMap): CartResource
    {
        $cartItems = [];
        $cartValue = Money::zero($cartDto->currency->value);

        foreach ($cartDto->getItems() as $item) {
            $cartItem = $cart->items->filter(
                fn (CartItemResource $value) => $value->cartitem_id === $item->getCartItemId(),
            )->first();

            if ($cartItem === null) {
                continue;
            }

            $cartItem = $this->applyDiscountOnCartItem($discount, $item, $cart, $priceMap);

            $cartItems[] = $cartItem;

            if ($priceMap->is_net) {
                $cartValue = $cartValue->plus($cartItem->price_discounted->net->multipliedBy($item->getQuantity()));
            } else {
                $cartValue = $cartValue->plus($cartItem->price_discounted->gross->multipliedBy($item->getQuantity()));
            }
        }

        $cart->items = CartItemResource::collection($cartItems);
        $cart->cart_total = OrderPriceDto::from($cartValue, $cart->cart_total->vat_rate, $priceMap->is_net);

        return $cart;
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws ServerException
     * @throws MoneyMismatchException
     */
    private function applyDiscountOnCartCheapestItem(
        Discount $discount,
        CartResource $cart,
        PriceMap $priceMap,
    ): CartResource {
        $cartItem = $cart->items->toCollection()->sortBy([
            ['price_discounted', 'asc'],
            ['quantity', 'asc'],
        ])->first();

        if ($cartItem === null) {
            return $cart;
        }

        $minimalProductPrice = Money::ofMinor(1, $cartItem->price_discounted->currency->toCurrencyInstance());

        if ($cartItem->quantity > 1 && !$cartItem->price_discounted->net->isLessThanOrEqualTo($minimalProductPrice)) {
            $cart->items = $cart->items->through(function (CartItemResource $value) use ($cartItem): CartItemResource {
                if ($value->cartitem_id === $cartItem->cartitem_id && $value->quantity === $cartItem->quantity) {
                    $value->quantity = $cartItem->quantity - 1;
                }

                return $value;
            });

            $cartItem = new CartItemResource(
                $cartItem->cartitem_id,
                $cartItem->price,
                $cartItem->price_discounted,
                $cartItem->currency,
                1,
            );
            $cart->items[] = $cartItem;
        }

        if (!$cartItem->price_discounted->net->isLessThanOrEqualTo($minimalProductPrice)) {
            $discount = $this->calc($cartItem->price_discounted->net, $discount);

            if ($priceMap->is_net) {
                $oldPrice = $cartItem->price_discounted->net;
                $newPrice = $cartItem->price_discounted->net->minus($discount);
            } else {
                $minimalProductPrice = $this->salesChannelService->addVat($minimalProductPrice, $cart->cart_total->vat_rate);
                $discount = $this->salesChannelService->addVat($discount, $cart->cart_total->vat_rate);

                $oldPrice = $cartItem->price_discounted->gross;
                $newPrice = $cartItem->price_discounted->gross->minus($discount);
            }

            $cartItem->price_discounted = OrderPriceDto::from(Money::max($newPrice, $minimalProductPrice), $cartItem->price_discounted->vat_rate, $priceMap->is_net);

            if ($priceMap->is_net) {
                $newCartTotal = $cart->cart_total->net->minus($oldPrice->minus($cartItem->price_discounted->net)->multipliedBy($cartItem->quantity));
            } else {
                $newCartTotal = $cart->cart_total->gross->minus($oldPrice->minus($cartItem->price_discounted->gross)->multipliedBy($cartItem->quantity));
            }

            $cart->cart_total = OrderPriceDto::from($newCartTotal, $cart->cart_total->vat_rate, $priceMap->is_net);
        }

        return $cart;
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     * @throws ServerException
     */
    private function applyDiscountOnOrderValue(Order $order, Discount $discount): Order
    {
        $appliedDiscount = $this->calcAppliedDiscount(
            $order->cart_total,
            $this->calc($order->cart_total, $discount),
            'minimal_order_price',
        );

        $order->cart_total = $order->cart_total->minus($appliedDiscount);
        $this->attachDiscount($order, $discount, $appliedDiscount);

        return $order;
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws MoneyMismatchException
     * @throws ServerException
     */
    private function applyDiscountOnOrderShipping(Order $order, Discount $discount): Order
    {
        if (
            in_array(
                $order->shipping_method_id,
                $discount->shippingMethods->pluck('id')->toArray(),
            ) === $discount->target_is_allow_list
        ) {
            $appliedDiscount = $this->calcAppliedDiscount(
                $order->shipping_price,
                $this->calc($order->shipping_price, $discount),
                'minimal_shipping_price',
            );

            $order->shipping_price = $order->shipping_price->minus($appliedDiscount);
            $this->attachDiscount($order, $discount, $appliedDiscount);
        }

        return $order;
    }

    /**
     * @throws ServerException
     */
    private function attachDiscount(Order|OrderProduct $object, Discount $discount, Money $appliedDiscount): void
    {
        $code = $discount->code !== null ? ['code' => $discount->code] : [];

        $amount = null;
        if ($discount->percentage === null) {
            [$dto] = $this->discountRepository->getDiscountAmounts($discount->getKey(), $object->currency);
            $amount = $dto->value;
        }

        $object->discounts()->attach(
            $discount->getKey(),
            [
                'name' => $discount->name,
                'amount' => $amount?->getMinorAmount(),
                'currency' => $object->currency,
                'percentage' => $discount->percentage,
                'target_type' => $discount->target_type,
                'applied' => $appliedDiscount->getMinorAmount(),
            ] + $code,
        );
    }

    /**
     * Checked conditions.
     */

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
                    if ($discount->allProductsIds()->doesntContain(
                        fn ($value): bool => $value === $item->getProductId(),
                    )) {
                        return true;
                    }
                }
            }
        }

        return in_array(
            $discount->target_type,
            [DiscountTargetType::ORDER_VALUE, DiscountTargetType::CHEAPEST_PRODUCT],
        );
    }

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
            ConditionType::USER_IN_ORGANIZATION => $checkForCurrentUser && $this->checkConditionUserInOrganization($condition),
        };
    }

    private function checkIsProductInDiscount(string $productId, Discount $discount): bool
    {
        $inDiscount = $this->checkIsProductInDiscountProducts($productId, $discount);

        if ($inDiscount !== $discount->target_is_allow_list) {
            return $this->checkIsProductInDiscountProductSets(
                $productId,
                $discount->productSets,
                $discount->target_is_allow_list,
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
        bool $allowList,
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
        bool $allowList,
    ): bool {
        if ($productSet->parent) {
            if ($productSets->contains($productSet->parent->id)) {
                return $allowList;
            }

            return $this->checkProductSetParentInDiscount($productSet->parent, $productSets, $allowList);
        }

        return !$allowList;
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    private function checkConditionOrderValue(DiscountCondition $condition, Money $cartValue): bool
    {
        $conditionDto = OrderValueConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        // TODO uwzględnić przy sprawdzaniu podatki $conditionDto->isIncludeTaxes()

        $minValue = $conditionDto->getMinValueForCurrency($cartValue->getCurrency()->getCurrencyCode());
        $maxValue = $conditionDto->getMaxValueForCurrency($cartValue->getCurrency()->getCurrencyCode());

        if ($minValue !== null && $maxValue !== null) {
            return ($cartValue->isGreaterThanOrEqualTo($minValue->value) && $cartValue->isLessThanOrEqualTo(
                $maxValue->value,
            )) === $conditionDto->isIsInRange();
        }

        if ($minValue !== null) {
            return $cartValue->isGreaterThanOrEqualTo($minValue->value) === $conditionDto->isIsInRange();
        }

        if ($maxValue !== null) {
            return $cartValue->isLessThanOrEqualTo($maxValue->value) === $conditionDto->isIsInRange();
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

    private function checkConditionUserInOrganization(DiscountCondition $condition): bool
    {
        /** @var UserInOrganizationConditionDto $conditionDto */
        $conditionDto = UserInOrganizationConditionDto::fromArray($condition->value + ['type' => $condition->type]);
        /** @var User|App|null $user */
        $user = Auth::user();

        if ($user instanceof User) {
            /** @var Organization $organization */
            foreach ($user->organizations as $organization) {
                if (in_array($organization->getKey(), $conditionDto->getOrganizations()) === $conditionDto->isIsAllowList()) {
                    return true;
                }
            }
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
                $conditionDto->isIsAllowList(),
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

        return $condition->conditionGroup?->discounts()->first()?->ordersWithUses()->count() < $conditionDto->getMaxUses();
    }

    private function checkConditionMaxUsesPerUser(DiscountCondition $condition): bool
    {
        $conditionDto = MaxUsesPerUserConditionDto::fromArray($condition->value + ['type' => $condition->type]);

        if (Auth::user()) {
            return $condition
                ->conditionGroup
                ?->discounts()
                ->first()
                ?->ordersWithUses()
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

    private function addDiscountToCartResource(
        OrderPriceDto $appliedDiscount,
        mixed $discount,
        CartResource $cartResource,
        PriceMap $priceMap,
    ): CartResource {
        if ($discount->code !== null) {
            $cartResource->coupons[] =
                new CouponShortResource(
                    $discount->getKey(),
                    $discount->name,
                    $appliedDiscount->net,
                    $appliedDiscount->currency,
                    $discount->code,
                );
        } else {
            $cartResource->sales[] =
                new SalesShortResource(
                    $discount->getKey(),
                    $discount->name,
                    $appliedDiscount->net,
                    $appliedDiscount->currency,
                );
        }

        return $cartResource;
    }
}
