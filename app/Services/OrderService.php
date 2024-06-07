<?php

namespace App\Services;

use App\DTO\OrderItemDto;
use App\Dtos\AddressDto;
use App\Dtos\CartDto;
use App\Dtos\CartItemDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderIndexDto;
use App\Dtos\OrderProductSearchDto;
use App\Dtos\OrderProductUpdateDto;
use App\Dtos\OrderProductUrlDto;
use App\Dtos\OrderUpdateDto;
use App\Enums\DiscountTargetType;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SchemaType;
use App\Enums\ShippingType;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Events\OrderUpdatedShippingNumber;
use App\Events\SendOrderUrls;
use App\Exceptions\ClientException;
use App\Exceptions\OrderException;
use App\Exceptions\ServerException;
use App\Exceptions\StoreException;
use App\Mail\OrderUrls;
use App\Models\Address;
use App\Models\App;
use App\Models\CartItemResponse;
use App\Models\CartResource;
use App\Models\CouponShortResource;
use App\Models\Discount;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\SalesShortResource;
use App\Models\Schema;
use App\Models\Status;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\Contracts\DepositServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\ItemServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Brick\Math\BigDecimal;
use App\Traits\ModifyLangFallback;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Domain\Price\Dtos\PriceDto;
use Domain\Order\Resources\OrderResource;
use Domain\Price\Enums\ProductPriceType;
use Domain\SalesChannel\SalesChannelService;
use Domain\ShippingMethod\Models\ShippingMethod;
use Exception;
use Heseya\Dto\Missing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App as AppSupport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\Facades\Mail;
use Throwable;

final readonly class OrderService implements OrderServiceContract
{
    use ModifyLangFallback;

    public function __construct(
        private DiscountServiceContract $discountService,
        private ItemServiceContract $itemService,
        private NameServiceContract $nameService,
        private MetadataServiceContract $metadataService,
        private DepositServiceContract $depositService,
        private ProductRepositoryContract $productRepository,
        private SalesChannelService $salesChannelService,
    ) {}

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function calcSummary(Order $order): Money
    {
        $value = Money::zero($order->currency->value);
        /** @var OrderProduct $item */
        foreach ($order->products as $item) {
            $value = $value->plus($item->price->multipliedBy($item->quantity));
        }

        foreach ($order->discounts as $discount) {
            $value = $value->minus($this->discountService->calc($value, $discount));
        }

        return $order->shipping_price->plus(Money::max($value, Money::zero($order->currency->value)));
    }

    /**
     * @throws Throwable
     * @throws OrderException
     * @throws ServerException
     */
    public function store(OrderDto $dto): Order
    {
        $currency = $dto->currency;
        $vat_rate = $this->salesChannelService->getVatRate($dto->sales_channel_id);

        DB::beginTransaction();

        try {
            $items = $dto->getItems();

            if ($items instanceof Missing) {
                throw new OrderException('Attempted to create an order without products!');
            }

            // Schema values and warehouse items validation
            $products = $this->itemService->checkOrderItems($items);

            [$shippingMethod, $digitalShippingMethod] = $this->getDeliveryMethods($dto, $products, true);

            // Creating order
            switch ($shippingMethod?->shipping_type) {
                case ShippingType::ADDRESS:
                    $shippingPlace = null;
                    $shippingAddressId = Address::query()
                        ->firstOrCreate($dto->getShippingPlace()->toArray()) // @phpstan-ignore-line
                        ->getKey();
                    break;
                case ShippingType::POINT:
                    $shippingPlace = null;
                    $shippingAddressId = Address::query() // @phpstan-ignore-line
                        ->find($dto->getShippingPlace())
                        ->getKey();
                    break;
                case ShippingType::POINT_EXTERNAL:
                    $shippingPlace = $dto->getShippingPlace();
                    $shippingAddressId = null;
                    break;
                default:
                    $shippingPlace = null;
                    $shippingAddressId = null;
                    break;
            }

            if ($shippingMethod) {
                $country = null;
                if ($shippingPlace instanceof AddressDto && is_string($shippingPlace->getCountry())) {
                    $country = $shippingPlace->getCountry();
                }
                if ($shippingAddressId) {
                    $country = Address::query()->where('id', $shippingAddressId)->first()?->country;
                }
                if ($country !== null && $shippingMethod->isCountryBlocked($country)) {
                    throw new OrderException(Exceptions::CLIENT_SHIPPING_METHOD_INVALID_COUNTRY);
                }
            }

            if (!($dto->getBillingAddress() instanceof Missing)) {
                $billingAddress = Address::query()->firstOrCreate($dto->getBillingAddress()->toArray());
            }

            $getInvoiceRequested = $dto->getInvoiceRequested() instanceof Missing
                ? false
                : $dto->getInvoiceRequested();

            /** @var Status $status */
            $status = Status::query()
                ->select('id')
                ->orderBy('order')
                ->firstOr(
                    callback: fn () => throw new ServerException(Exceptions::SERVER_ORDER_STATUSES_NOT_CONFIGURED),
                );

            /** @var User|App $buyer */
            $buyer = Auth::user();

            /** @var Order $order */
            $order = Order::query()->create(
                [
                    'code' => $this->nameService->generate(),
                    'currency' => $currency->value,
                    'shipping_price_initial' => Money::zero($currency->value),
                    'shipping_price' => Money::zero($currency->value),
                    'cart_total_initial' => Money::zero($currency->value),
                    'cart_total' => Money::zero($currency->value),
                    'summary' => Money::zero($currency->value),
                    'status_id' => $status->getKey(),
                    'shipping_address_id' => $shippingAddressId,
                    'billing_address_id' => isset($billingAddress) ? $billingAddress->getKey() : null,
                    'buyer_id' => $buyer->getKey(),
                    'buyer_type' => $buyer->getMorphClass(),
                    'invoice_requested' => $getInvoiceRequested,
                    'shipping_place' => $shippingPlace,
                    'shipping_type' => $shippingMethod->shipping_type ?? $digitalShippingMethod->shipping_type ?? null,
                ] + $dto->toArray(),
            );

            if (!($dto->getMetadata() instanceof Missing)) {
                $this->metadataService->sync($order, $dto->getMetadata());
            }

            // Add products to order
            $tempSchemaOrderProduct = [];

            // Used to calc products discounts
            $priceMatrix = [];
            $productIds = [];

            try {
                $previousSettings = $this->getCurrentLangFallbackSettings();
                $this->setAnyLangFallback();
                $language = AppSupport::getLocale();
                foreach ($items as $item) {
                    /** @var Product $product */
                    $product = $products->firstWhere('id', $item->getProductId());
                    $productIds[] = $item->getProductId();

                    $prices = $this->productRepository->getProductPrices($product->getKey(), [
                        ProductPriceType::PRICE_BASE,
                    ], $currency);

                    /** @var PriceDto $priceDto */
                    $priceDto = $prices->get(ProductPriceType::PRICE_BASE->value)->firstOrFail();
                    $price = $priceDto->value;

                    $orderProduct = new OrderProduct([
                        'product_id' => $item->getProductId(),
                        'quantity' => $item->getQuantity(),
                        'currency' => $order->currency,
                        'price_initial' => $price,
                        'price' => $price,
                        'base_price_initial' => $price,
                        'base_price' => $price,
                        'name' => $product->getTranslation('name', $language),
                        'vat_rate' => $vat_rate->multipliedBy(100)->toFloat(),
                        'shipping_digital' => $product->shipping_digital,
                    ]);

                    $order->products()->save($orderProduct);

                    $schemaProductPrice = Money::zero($currency->value);
                    // Add schemas to products
                    foreach ($item->getSchemas() as $schemaId => $value) {
                        /** @var Schema $schema */
                        $schema = $product->schemas()->findOrFail($schemaId);
                        $price = $schema->getPrice($value, $item->getSchemas(), $currency);

                        if ($schema->type === SchemaType::SELECT) {
                            /** @var Option $option */
                            $option = $schema->options()->findOrFail($value);
                            $tempSchemaOrderProduct[$schema->name . '_' . $item->getProductId()] = [$schemaId, $value];
                            $value = $option->name;
                        }

                        $orderProduct->schemas()->create([
                            'name' => $schema->getTranslation('name', $language),
                            'value' => $value,
                            'price_initial' => $price,
                            'price' => $price,
                        ]);

                        $schemaProductPrice = $schemaProductPrice->plus($price);
                    }

                    if ($schemaProductPrice->isPositive()) {
                        $orderProduct->price = $schemaProductPrice->plus($orderProduct->price);
                        $orderProduct->price_initial = $schemaProductPrice->plus($orderProduct->price_initial);
                        $orderProduct->save();
                    }

                    $priceMatrix[$item->getProductId()] = [
                        'base_price' => [$priceDto],
                        'price' => [PriceDto::fromMoney($orderProduct->price)],
                    ];
                }
                $this->setLangFallbackSettings(...$previousSettings);

                $productDiscounts = $this->discountService->getAllDiscountsForProducts($productIds, $dto->getCoupons());

                $discountedProductPrices = $this->discountService->discountProductPrices($priceMatrix, $productDiscounts);

                // Discount cheapest product
                $cheapestProductId = null;
                /** @var PriceDto[][] $cheapestPrices */
                $cheapestPrices = null;
                foreach ($discountedProductPrices as $productId => $typedPrices) {
                    /**
                     * @var Money $cheapestPrice
                     */
                    $cheapestPrice = $cheapestPrices['price']->value;
                    /**
                     * @var Money $currentMoney
                     */
                    $currentMoney = $typedPrices['price']->value;
                    if ($cheapestPrice === null || $currentMoney->isLessThan($cheapestPrice)) {
                        $cheapestPrices = $typedPrices;
                        $cheapestProductId = $productId;
                    }
                }

                $cheapestProductDiscounts = $this->discountService->getAllDiscountsOfType(
                    DiscountTargetType::CHEAPEST_PRODUCT,
                    $dto->getCoupons(),
                );

                $discountedCheapestProduct = $this->discountService->discountPricesRaw([
                    $cheapestProductId => $cheapestPrices,
                ], $cheapestProductDiscounts);

                $discountedProductPrices = $discountedCheapestProduct + $discountedProductPrices;

                // Update order products prices
                $cartValue = Money::zero($currency->value);
                $rows = $order->products->map(function (OrderProduct $product) use ($discountedProductPrices, &$cartValue) {
                    $price = $discountedProductPrices[$product->product_id]['price'][0]->value;
                    $cartValue = $cartValue->plus($price->multipliedBy($product->quantity));

                    return [
                        'id' => $product->getKey(),
                        'price' => $price,
                    ];
                });
                OrderProduct::query()->upsert($rows, ['id']);

                $orderDiscounts = $this->discountService->getAllDiscountsOfType(
                    DiscountTargetType::ORDER_VALUE,
                    $dto->getCoupons(),
                );
                [[$cartValueDiscounted]] = $this->discountService->discountPricesRaw([[
                    PriceDto::fromMoney($cartValue),
                ]], $orderDiscounts);

                $order->cart_total_initial = $cartValue;
                $order->cart_total = $cartValueDiscounted;

//                // Apply discounts to order/products
//                $order = $this->discountService->calcOrderProductsAndTotalDiscounts($order, $dto);

                $shippingPrice = $shippingMethod?->getPrice($order->cart_total) ?? Money::zero($currency->value);
                $shippingPrice = $shippingPrice->plus(
                    $digitalShippingMethod?->getPrice($order->cart_total) ?? Money::zero($currency->value),
                );

                $shippingDiscounts = $this->discountService->getAllDiscountsOfType(
                    DiscountTargetType::SHIPPING_PRICE,
                    $dto->getCoupons(),
                );
                [[$shippingPriceDiscounted]] = $this->discountService->discountPricesRaw([[
                    PriceDto::fromMoney($shippingPrice),
                ]], $shippingDiscounts);

                // Always gross
                $order->shipping_price_initial = $shippingPrice;
                $order->shipping_price = $shippingPriceDiscounted;

//                // Apply discounts to order
//                $order = $this->discountService->calcOrderShippingDiscounts($order, $dto);

                /** @var OrderProduct $orderProduct */
                foreach ($order->products as $orderProduct) {
                    // Remove items from warehouse
                    if (!$this->removeItemsFromWarehouse($orderProduct, $tempSchemaOrderProduct)) {
                        throw new OrderException(Exceptions::ORDER_NOT_ENOUGH_ITEMS_IN_WAREHOUSE);
                    }

                    $orderProduct->base_price_initial = $this->salesChannelService->addVat(
                        $orderProduct->base_price_initial,
                        $vat_rate,
                    );
                    $orderProduct->base_price = $this->salesChannelService->addVat(
                        $orderProduct->base_price,
                        $vat_rate,
                    );
                    $orderProduct->price_initial = $this->salesChannelService->addVat(
                        $orderProduct->price_initial,
                        $vat_rate,
                    );
                    $orderProduct->price = $this->salesChannelService->addVat(
                        $orderProduct->price,
                        $vat_rate,
                    );

                    /** @var Discount $discount */
                    foreach ($orderProduct->discounts as $discount) {
                        if ($discount->order_discount?->applied !== null) {
                            $discount->order_discount->applied = $this->salesChannelService->addVat(
                                $discount->order_discount->applied,
                                $vat_rate,
                            );
                            $discount->order_discount->save();
                        }
                    }
                }

                /** @var Discount $discount */
                foreach ($order->discounts as $discount) {
                    if ($discount->order_discount?->applied !== null) {
                        $discount->order_discount->applied = $this->salesChannelService->addVat(
                            $discount->order_discount->applied,
                            $vat_rate,
                        );
                        $discount->order_discount->save();
                    }
                }

                $order->cart_total_initial = $this->salesChannelService->addVat(
                    $order->cart_total_initial,
                    $vat_rate,
                );
                $order->cart_total = $this->salesChannelService->addVat(
                    $order->cart_total,
                    $vat_rate,
                );

                $order->summary = $order->shipping_price->plus($order->cart_total);

                $order->push();
            } catch (Throwable $exception) {
                $order->delete();

                throw $exception;
            }

            DB::commit();
            OrderCreated::dispatch($order);

            return $order;
        } catch (StoreException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (Throwable $throwable) {
            DB::rollBack();

            throw new ServerException(Exceptions::SERVER_TRANSACTION_ERROR, $throwable);
        }
    }

    /**
     * @throws ClientException
     */
    public function update(OrderUpdateDto $dto, Order $order): JsonResponse
    {
        DB::beginTransaction();
        try {
            [$shippingMethod, $digitalShippingMethod] = $this->getDeliveryMethods($dto, $order->products, false);

            $shippingType = $shippingMethod
                ? $shippingMethod->shipping_type
                : $order->shippingMethod?->shipping_type;

            if ($shippingType === null) {
                $shippingType = $digitalShippingMethod
                    ? $digitalShippingMethod->shipping_type : $order->digitalShippingMethod?->shipping_type;
            }

            /** @var ShippingType $shippingType */
            if ($shippingType !== ShippingType::POINT) {
                $shippingPlace = $dto->getShippingPlace() instanceof AddressDto ?
                    $this->modifyAddress(
                        $order,
                        'shipping_address_id',
                        $dto->getShippingPlace(),
                    ) : $dto->getShippingPlace();
            } else {
                if (!($dto->getShippingPlace() instanceof Missing)) {
                    /** @phpstan-ignore-next-line */
                    $shippingPlace = Address::query()->find($dto->getShippingPlace())->getKey();
                } else {
                    $shippingPlace = null;
                }
            }

            $billingAddress = $this->modifyAddress(
                $order,
                'billing_address_id',
                $dto->getBillingAddress(),
            );

            $billingAddressId = $billingAddress instanceof Address
                ? ['billing_address_id' => $billingAddress->getKey()]
                : (!$dto->getBillingAddress() instanceof Missing ? ['billing_address_id' => null] : []);

            $order->update(
                [
                    'shipping_address_id' => $this->resolveShippingAddress($shippingPlace, $shippingType, $order),
                    'shipping_place' => $this->resolveShippingPlace($shippingPlace, $shippingType, $order),
                    'shipping_type' => $shippingType,
                ] + $dto->toArray() + $billingAddressId,
            );

            if ($shippingMethod) {
                $order->shippingMethod()->dissociate();
                $order->shippingMethod()->associate($shippingMethod);
            }

            if ($digitalShippingMethod) {
                $order->digitalShippingMethod()->dissociate();
                $order->digitalShippingMethod()->associate($digitalShippingMethod);
            }

            DB::commit();

            // other event when only shipping number is updated
            if (!($dto->getShippingNumber() instanceof Missing)) {
                OrderUpdatedShippingNumber::dispatch($order);

                if (count($dto->toArray()) !== 1) {
                    OrderUpdated::dispatch($order);
                }
            } else {
                OrderUpdated::dispatch($order);
            }

            return OrderResource::make($order)->response();
        } catch (Exception $error) {
            DB::rollBack();

            throw new ClientException(Exceptions::CLIENT_ORDER_EDIT_ERROR, $error);
        }
    }

    public function indexUserOrder(OrderIndexDto $dto): LengthAwarePaginator
    {
        return Order::searchByCriteria(['buyer_id' => Auth::id()] + $dto->getSearchCriteria())
            ->sort($dto->getSort())
            ->with([
                'products',
                'discounts',
                'payments',
                'status',
                'shippingMethod',
                'shippingMethod.paymentMethods',
                'digitalShippingMethod',
                'digitalShippingMethod.paymentMethods',
                'shippingAddress',
                'metadata',
                'documents',
            ])
            ->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @throws OrderException
     */
    public function cartProcess(CartDto $cartDto): CartResource
    {
        $vat_rate = $this->salesChannelService->getVatRate($cartDto->sales_channel_id);

        // Lista tylko dostępnych produktów
        [$products, $items] = $this->itemService->checkCartItems($cartDto->getItems());
        $cartDto->setItems($items);

        if ($products->isNotEmpty()) {
            $this->getDeliveryMethods($cartDto, $products, false);
        }

        return $this->calcCartDiscounts($cartDto, $products, $vat_rate);
    }

    /**
    //     * @throws MathException
    //     * @throws StoreException
    //     * @throws MoneyMismatchException
    //     * @throws UnknownCurrencyException
    //     * @throws DtoException
    //     */
    private function calcCartDiscounts(CartDto $dto): CartResource
    {
        $currency = $dto->currency;
        $vat_rate = $this->salesChannelService->getVatRate($dto->sales_channel_id);

        // TODO: Order items cannot be missing

        // Schema values and warehouse items validation
        $products = $this->itemService->checkOrderItems($dto->getItems());

        [$shippingMethod, $digitalShippingMethod] = $this->getDeliveryMethods($dto, $products, true);

        /** @var User|App $buyer */
        $buyer = Auth::user();

        // Used to calc products discounts
        $itemPrices = [];
        $productIds = [];

        /** @var CartItemDto $item */
        foreach ($dto->getItems() as $item) {
            $productId = $item->getProductId();
            /** @var Product $product */
            $product = $products->firstWhere('id', $productId);
            $productIds[] = $productId;

            $schemaProductPrice = Money::zero($currency->value);
            // Add schemas to products
            foreach ($item->getSchemas() as $schemaId => $value) {
                /** @var Schema $schema */
                $schema = $product->schemas->findOrFail($schemaId);
                $price = $schema->getPrice($value, $item->getSchemas(), $currency);

                $schemaProductPrice = $schemaProductPrice->plus($price);
            }

            $itemPrices[$productId][$item->getCartItemId()] = $schemaProductPrice;
        }

        $productDiscounts = $this->discountService->getAllDiscountsForProducts($productIds, $dto->getCoupons());

        $productPrices = $this->productRepository->getProductsPrices($productIds, [
            ProductPriceType::PRICE_BASE,
        ], $currency);

        foreach ($itemPrices as $productId => $itemedPrices) {
            $productPrice = $productPrices[$productId][ProductPriceType::PRICE_BASE->value][0];

            foreach ($itemedPrices as $itemId => $schemasPrice) {
                $itemPrices[$productId][$itemId] = [PriceDto::fromMoney($productPrice->value->plus($schemasPrice))];
            }
        }

        $itemPrices = $this->discountService->discountProductPrices($itemPrices, $productDiscounts);

        // Discount cheapest product
        $cheapestProductId = null;
        $cheapestItemId = null;
        foreach ($itemPrices as $productId => $itemedPrices) {
            foreach ($itemedPrices as $itemId => $prices) {
                $cheapestPrice = $itemPrices[$cheapestProductId][$cheapestItemId][0]?->value;
                $currentMoney = $prices[0]->value;

                if ($cheapestProductId === null ||
                    $cheapestItemId === null ||
                    $currentMoney->isLessThan($cheapestPrice)
                ) {
                    $cheapestProductId = $productId;
                    $cheapestItemId = $itemId;
                }
            }
        }

        $cheapestProductDiscounts = $this->discountService->getAllDiscountsOfType(
            DiscountTargetType::CHEAPEST_PRODUCT,
            $dto->getCoupons(),
        );

        $discountedCheapestProduct = $this->discountService->discountPricesRaw([
            $cheapestProductId => [
                $cheapestItemId => $itemPrices[$cheapestProductId][$cheapestItemId],
            ],
        ], $cheapestProductDiscounts);

        $itemPrices[$cheapestProductId][$cheapestItemId] = $discountedCheapestProduct[$cheapestProductId][$cheapestItemId];

        $cartValue = Money::zero($currency->value);
        /** @var CartItemDto $item */
        foreach ($dto->getItems() as $item) {
            $itemPrice = $itemPrices[$item->getProductId()][$item->getCartItemId()][0]->value;

            $cartValue = $itemPrice->multipliedBy($item->getQuantity())->plus($cartValue);
        }

        $orderDiscounts = $this->discountService->getAllDiscountsOfType(
            DiscountTargetType::ORDER_VALUE,
            $dto->getCoupons(),
        );

        /** @var PriceDto $cartValue */
        [[$cartValue]] = $this->discountService->discountPricesRaw([[
            PriceDto::fromMoney($cartValue),
        ]], $orderDiscounts);

        $shippingPrice = $shippingMethod?->getPrice($cartValue->value) ?? Money::zero($currency->value);
        $shippingPrice = $shippingPrice->plus(
            $digitalShippingMethod?->getPrice($cartValue->value) ?? Money::zero($currency->value),
        );

        $shippingDiscounts = $this->discountService->getAllDiscountsOfType(
            DiscountTargetType::SHIPPING_PRICE,
            $dto->getCoupons(),
        );
        /** @var PriceDto $shippingPrice */
        [[$shippingPrice]] = $this->discountService->discountPricesRaw([[
            PriceDto::fromMoney($shippingPrice),
        ]], $shippingDiscounts);


        // Add vat to product prices
        foreach ($itemPrices as $productId => $itemedPrices) {
            foreach ($itemedPrices as $itemId => $prices) {
                $itemPrices[$productId][$itemId][0] = PriceDto::fromMoney(
                    $this->salesChannelService->addVat($prices[0], $vat_rate),
                );
            }
        }

        $cartValue = PriceDto::fromMoney($this->salesChannelService->addVat($cartValue->value, $vat_rate));
        $shippingPrice = PriceDto::fromMoney($this->salesChannelService->addVat($shippingPrice->value, $vat_rate));

        $cartItemDto = [];
        /** @var CartItemDto $item **/
        foreach ($dto->getItems() as $item) {
            $cartItemDto[] = new OrderItemDto(
                $item->getCartItemId(),
                $item->getProductId(),
                $products->firstWhere('id', $item->getProductId())->name,

//                $price,
//                $price_initial,
//                $price_base,
//                $price_base_initial,
//                $quantity,
//                $schemas,
//                $discounts,
            );
        }







        foreach ($cart->getItems() as $cartItem) {
            $product = $products->firstWhere('id', $cartItem->getProductId());

            if (!($product instanceof Product)) {
                // skip when product is not available
                continue;
            }

            $prices = $this->productRepository->getProductPrices($product->getKey(), [
                ProductPriceType::PRICE_BASE,
            ], $currency);

            /** @var Money $price */
            $price = $prices->get(ProductPriceType::PRICE_BASE->value)?->firstOrFail(
            )?->value ?? throw new ItemNotFoundException();

            foreach ($cartItem->getSchemas() as $schemaId => $value) {
                /** @var Schema $schema */
                $schema = $product->schemas()->findOrFail($schemaId);

                $price = $price->plus(
                    $schema->getPrice($value, $cartItem->getSchemas(), $currency),
                );
            }

//            $cartValue = $cartValue->plus($price->multipliedBy($cartItem->getQuantity()));
            $cartItems[] = new CartItemResponse(
                $cartItem->getCartItemId(),
                $price,
                $price,
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
            $cartValue,
            $cartValue,
            $shippingPrice,
            $shippingPrice,
            $summary,
            $cartShippingTimeAndDate['shipping_time'] ?? null,
            $cartShippingTimeAndDate['shipping_date'] ?? null,
        );

        if ($cartResource->items->isEmpty()) {
            return $cartResource;
        }

//        foreach ($discounts as $discount) {
//            if (
//                $this->checkDiscountTarget($discount, $cart)
//                && $this->checkConditionGroups($discount, $cart, $cartResource->cart_total)
//            ) {
//                $cartResource = $this->applyDiscountOnCart($discount, $cart, $cartResource);
//                $newSummary = $cartResource->cart_total->plus($cartResource->shipping_price);
//                $appliedDiscount = $summary->minus($newSummary);
//
//                if ($discount->code !== null) {
//                    $cartResource->coupons->push(
//                        new CouponShortResource(
//                            $discount->getKey(),
//                            $discount->name,
//                            $appliedDiscount,
//                            $discount->code,
//                        ),
//                    );
//                } else {
//                    $cartResource->sales->push(
//                        new SalesShortResource(
//                            $discount->getKey(),
//                            $discount->name,
//                            $appliedDiscount,
//                        ),
//                    );
//                }
//
//                $summary = $newSummary;
//            }
//        }

        foreach ($cartResource->items as $item) {
            $item->price = $this->salesChannelService->addVat($item->price, $vat_rate);
            $item->price_discounted = $this->salesChannelService->addVat($item->price_discounted, $vat_rate);
        }

        $cartResource->cart_total_initial = $this->salesChannelService->addVat(
            $cartResource->cart_total_initial,
            $vat_rate,
        );
        $cartResource->cart_total = $this->salesChannelService->addVat($cartResource->cart_total, $vat_rate);
        $cartResource->summary = $cartResource->cart_total->plus($cartResource->shipping_price);

        return $cartResource;
    }

    public function processOrderProductUrls(OrderProductUpdateDto $dto, OrderProduct $product): OrderProduct
    {
        if (!$dto->getIsDelivered() instanceof Missing) {
            $product->update([
                'is_delivered' => $dto->getIsDelivered(),
            ]);
        }

        if (!$dto->getUrls() instanceof Missing) {
            /** @var OrderProductUrlDto $url */
            foreach ($dto->getUrls() as $url) {
                if ($url->getUrl() === null) {
                    $product->urls()->where('name', $url->getName())->delete();
                    continue;
                }
                $product->urls()->updateOrCreate(
                    ['name' => $url->getName()],
                    $url->toArray(),
                );
            }
        }

        return $product;
    }

    public function indexMyOrderProducts(OrderProductSearchDto $dto): LengthAwarePaginator
    {
        return OrderProduct::searchByCriteria(
            ['user' => Auth::id(), 'paid' => true] + $dto->toArray(),
        )
            ->sort('created_at:desc')
            ->with(['urls', 'product'])
            ->paginate(Config::get('pagination.per_page'));
    }

    public function sendUrls(Order $order): void
    {
        $products = $order->products()->has('urls')->get();
        if (!$products->isEmpty()) {
            Mail::to($order->email)
                ->locale($order->locale)
                ->send(new OrderUrls($order, $products));

            $products->toQuery()->update([
                'is_delivered' => true,
            ]);

            SendOrderUrls::dispatch($order);
        }
    }

    /**
     * @return (ShippingMethod|null)[]
     *
     * @throws OrderException
     */
    private function getDeliveryMethods(
        CartDto|OrderDto|OrderUpdateDto $dto,
        Collection $products,
        bool $required,
    ): array {
        try {
            // Validate whether delivery methods are the proper type
            $shippingMethod = $dto->getShippingMethodId() instanceof Missing ? null :
                ShippingMethod::whereNot('shipping_type', ShippingType::DIGITAL->value)
                    ->findOrFail($dto->getShippingMethodId());

            $digitalShippingMethod = $dto->getDigitalShippingMethodId() instanceof Missing ? null :
                ShippingMethod::where('shipping_type', ShippingType::DIGITAL->value)
                    ->findOrFail($dto->getDigitalShippingMethodId());
        } catch (Throwable $e) {
            throw new OrderException(Exceptions::CLIENT_SHIPPING_METHOD_INVALID_TYPE);
        }

        // Validate whether there are products suited to given delivery types
        // if delivery type isn't required, it's ignored when missing
        if (!$this->itemService->checkHasItemType(
            $products,
            $shippingMethod !== null ? true : ($required ? false : null),
            $digitalShippingMethod !== null ? true : ($required ? false : null),
        )) {
            throw new OrderException(Exceptions::ORDER_SHIPPING_METHOD_TYPE_MISMATCH);
        }

        return [
            $shippingMethod,
            $digitalShippingMethod,
        ];
    }

    private function checkAddress(AddressDto|Missing $addressDto): bool
    {
        if ($addressDto instanceof Missing) {
            return false;
        }

        $address = $addressDto->toArray();
        foreach ($address as $item) {
            if ($item !== null) {
                $exsistAddress = true;
            }
        }

        return isset($exsistAddress);
    }

    private function modifyAddress(Order $order, string $attribute, AddressDto|Missing $addressDto): ?Address
    {
        if ($addressDto instanceof Missing || !$this->checkAddress($addressDto)) {
            return null;
        }

        $old = Address::find($order->{$attribute});
        Cache::add('address.' . $order->{$attribute}, $old ? ((string) $old) : null);
        if ($attribute === 'shipping_address_id' && $order->shipping_type === ShippingType::POINT) {
            return Address::create($addressDto->toArray());
        }

        return Address::updateOrCreate(['id' => $order->{$attribute}], $addressDto->toArray());
    }

    private function removeItemsFromWarehouse(OrderProduct $orderProduct, array $tempSchemaOrderProduct): bool
    {
        $itemsToRemove = [];
        /** @var Product $product */
        $product = $orderProduct->product;
        $productItems = $product->items;
        foreach ($productItems as $productItem) {
            $quantity = $productItem->pivot->required_quantity * $orderProduct->quantity;

            if (!isset($itemsToRemove[$productItem->getKey()])) {
                $itemsToRemove[$productItem->getKey()] = ['item' => $productItem, 'quantity' => $quantity];
            } else {
                $itemsToRemove[$productItem->getKey()]['quantity'] += $quantity;
            }
        }

        foreach ($orderProduct->schemas as $schemaOrder) {
            if (isset($tempSchemaOrderProduct[$schemaOrder->name . '_' . $product->getKey()])) {
                $value = $tempSchemaOrderProduct[$schemaOrder->name . '_' . $product->getKey()][1];
                $schemaId = $tempSchemaOrderProduct[$schemaOrder->name . '_' . $product->getKey()][0];

                $schema = $product->schemas()->findOrFail($schemaId);
                $option = $schema->options()->findOrFail($value);
                foreach ($option->items as $optionItem) {
                    $quantity = $orderProduct->quantity;
                    if (!isset($itemsToRemove[$optionItem->getKey()])) {
                        $itemsToRemove[$optionItem->getKey()] = ['item' => $optionItem, 'quantity' => $quantity];
                    } else {
                        $itemsToRemove[$optionItem->getKey()]['quantity'] += $quantity;
                    }
                }
            }
        }

        return !$itemsToRemove || $this->depositService->removeItemsFromWarehouse($itemsToRemove, $orderProduct);
    }

    private function resolveShippingAddress(
        Address|Missing|string|null $shippingPlace,
        ShippingType $shippingType,
        Order $order,
    ): ?string {
        if ($shippingPlace instanceof Missing) {
            return $order->shipping_address_id;
        }

        switch ($shippingType) {
            case ShippingType::POINT:
                if ($order->shippingMethod?->shipping_type === ShippingType::POINT && !$shippingPlace) {
                    return $order->shipping_address_id;
                }

                return $shippingPlace;
            case ShippingType::ADDRESS:
                if ($order->shippingMethod?->shipping_type === ShippingType::ADDRESS && !$shippingPlace) {
                    return $order->shipping_address_id;
                }

                if (!($shippingPlace instanceof Address)) {
                    throw new ServerException('Attempting to resolve shipping of type address but place is not Address');
                }

                return $shippingPlace->getKey();
            default:
                return null;
        }
    }

    private function resolveShippingPlace(
        Address|Missing|string|null $shippingPlace,
        ShippingType $shippingType,
        Order $order,
    ): ?string {
        if ($shippingPlace instanceof Missing) {
            return $order->shipping_place;
        }

        return match ($shippingType) {
            ShippingType::POINT_EXTERNAL => $shippingPlace,
            default => null,
        };
    }
}

