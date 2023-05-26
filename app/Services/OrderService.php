<?php

namespace App\Services;

use App\Dtos\AddressDto;
use App\Dtos\CartDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderIndexDto;
use App\Dtos\OrderProductSearchDto;
use App\Dtos\OrderProductUpdateDto;
use App\Dtos\OrderProductUrlDto;
use App\Dtos\OrderUpdateDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SchemaType;
use App\Enums\ShippingType;
use App\Events\OrderCreated;
use App\Events\OrderRequestedShipping;
use App\Events\OrderUpdated;
use App\Events\OrderUpdatedShippingNumber;
use App\Events\SendOrderUrls;
use App\Exceptions\ClientException;
use App\Exceptions\OrderException;
use App\Exceptions\ServerException;
use App\Exceptions\StoreException;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\App;
use App\Models\CartResource;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PackageTemplate;
use App\Models\Product;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\User;
use App\Notifications\SendUrls;
use App\Services\Contracts\DepositServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\ItemServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Exception;
use Heseya\Dto\Missing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderService implements OrderServiceContract
{
    public function __construct(
        private DiscountServiceContract $discountService,
        private ItemServiceContract $itemService,
        private NameServiceContract $nameService,
        private MetadataServiceContract $metadataService,
        private DepositServiceContract $depositService
    ) {
    }

    public function calcSummary(Order $order): float
    {
        $value = 0;
        foreach ($order->products as $item) {
            $value += $item->price * $item->quantity;
        }

        $cartValue = $value;
        foreach ($order->discounts as $discount) {
            $value -= $this->discountService->calc($cartValue, $discount);
        }

        $value = max($value, 0) + $order->shipping_price;

        return round($value, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * @throws Throwable
     * @throws OrderException
     * @throws ServerException
     */
    public function store(OrderDto $dto): Order
    {
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
                ->firstOr(callback: fn () => throw new ServerException(Exceptions::SERVER_ORDER_STATUSES_NOT_CONFIGURED));

            /** @var User|App $buyer */
            $buyer = Auth::user();

            /** @var Order $order */
            $order = Order::query()->create(
                [
                    'code' => $this->nameService->generate(),
                    'currency' => 'PLN',
                    'shipping_price_initial' => 0.0,
                    'shipping_price' => 0.0,
                    'cart_total_initial' => 0.0,
                    'cart_total' => 0.0,
                    'status_id' => $status->getKey(),
                    'shipping_address_id' => $shippingAddressId,
                    'billing_address_id' => isset($billingAddress) ? $billingAddress->getKey() : null,
                    'buyer_id' => $buyer->getKey(),
                    'buyer_type' => $buyer::class,
                    'invoice_requested' => $getInvoiceRequested,
                    'shipping_place' => $shippingPlace,
                    'shipping_type' => $shippingMethod->shipping_type ?? $digitalShippingMethod->shipping_type,
                ] + $dto->toArray(),
            );

            // Add products to order
            $cartValueInitial = 0;
            $tempSchemaOrderProduct = [];
            try {
                foreach ($items as $item) {
                    /** @var Product $product */
                    $product = $products->firstWhere('id', $item->getProductId());

                    $orderProduct = new OrderProduct([
                        'product_id' => $item->getProductId(),
                        'quantity' => $item->getQuantity(),
                        'price_initial' => $product->price,
                        'price' => $product->price,
                        'base_price_initial' => $product->price,
                        'base_price' => $product->price,
                        'name' => $product->name,
                        'vat_rate' => $product->vat_rate,
                        'shipping_digital' => $product->shipping_digital,
                    ]);

                    $order->products()->save($orderProduct);
                    $cartValueInitial += $product->price * $item->getQuantity();

                    $schemaProductPrice = 0;
                    // Add schemas to products
                    foreach ($item->getSchemas() as $schemaId => $value) {
                        /** @var Schema $schema */
                        $schema = $product->schemas()->findOrFail($schemaId);
                        $price = $schema->getPrice($value, $item->getSchemas());

                        if ($schema->type->is(SchemaType::SELECT)) {
                            /** @var Option $option */
                            $option = $schema->options()->findOrFail($value);
                            $tempSchemaOrderProduct[$schema->name . '_' . $item->getProductId()] = [$schemaId, $value];
                            $value = $option->name;
                        }

                        $orderProduct->schemas()->create([
                            'name' => $schema->name,
                            'value' => $value,
                            'price_initial' => $price,
                            'price' => $price,
                        ]);

                        $schemaProductPrice += $price;
                        $cartValueInitial += $price * $item->getQuantity();
                    }

                    if ($schemaProductPrice) {
                        $orderProduct->price += $schemaProductPrice;
                        $orderProduct->price_initial += $schemaProductPrice;
                        $orderProduct->save();
                    }
                }

                if (!($dto->getMetadata() instanceof Missing)) {
                    $this->metadataService->sync($order, $dto->getMetadata());
                }

                $order->cart_total_initial = $cartValueInitial;
                $order->cart_total = $cartValueInitial;

                // Apply discounts to order/products
                $order = $this->discountService->calcOrderProductsAndTotalDiscounts($order, $dto);

                $shippingPrice = $shippingMethod?->getPrice($order->cart_total) ?? 0;
                $shippingPrice += $digitalShippingMethod?->getPrice($order->cart_total) ?? 0;

                $order->shipping_price_initial = $shippingPrice;
                $order->shipping_price = $shippingPrice;

                // Apply discounts to order
                $order = $this->discountService->calcOrderShippingDiscounts($order, $dto);

                foreach ($order->products as $orderProduct) {
                    // Remove items from warehouse
                    if (!$this->removeItemsFromWarehouse($orderProduct, $tempSchemaOrderProduct)) {
                        throw new OrderException(Exceptions::ORDER_NOT_ENOUGH_ITEMS_IN_WAREHOUSE);
                    }
                }
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
        } catch (Throwable) {
            DB::rollBack();

            throw new ServerException(Exceptions::SERVER_TRANSACTION_ERROR);
        }
    }

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

            if ($shippingType !== ShippingType::POINT) {
                $shippingPlace = $dto->getShippingPlace() instanceof AddressDto ?
                    $this->modifyAddress(
                        $order,
                        'shipping_address_id',
                        $dto->getShippingPlace(),
                    ) : $dto->getShippingPlace();
            } else {
                if (!($dto->getShippingPlace() instanceof Missing)) {
                    // @phpstan-ignore-next-line
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

            $order->update([
                'shipping_address_id' => $this->resolveShippingAddress($shippingPlace, $shippingType, $order),
                'shipping_place' => $this->resolveShippingPlace($shippingPlace, $shippingType, $order),
                'shipping_type' => $shippingType,
            ] + $dto->toArray() + $billingAddressId);

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

            throw new ClientException(Exceptions::CLIENT_ORDER_EDIT_ERROR);
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

    public function shippingList(Order $order, string $packageTemplateId): Order
    {
        $packageTemplate = PackageTemplate::findOrFail($packageTemplateId);
        OrderRequestedShipping::dispatch($order, $packageTemplate);

        return $order;
    }

    public function cartProcess(CartDto $cartDto): CartResource
    {
        // Lista tylko dostępnych produktów
        [$products, $items] = $this->itemService->checkCartItems($cartDto->getItems());
        $cartDto->setItems($items);

        if ($products->isNotEmpty()) {
            $this->getDeliveryMethods($cartDto, $products, false);
        }

        return $this->discountService->calcCartDiscounts($cartDto, $products);
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
            ['user' => Auth::id(), 'paid' => true] + $dto->toArray()
        )
            ->sort('created_at:desc')
            ->with(['urls', 'product'])
            ->paginate(Config::get('pagination.per_page'));
    }

    public function sendUrls(Order $order): void
    {
        $products = $order->products()->has('urls')->get();
        if (!$products->isEmpty()) {
            $order->notify(new SendUrls($order, $products));

            $products->toQuery()->update([
                'is_delivered' => true,
            ]);

            SendOrderUrls::dispatch($order);
        }
    }

    private function getDeliveryMethods(
        OrderDto|CartDto|OrderUpdateDto $dto,
        Collection $products,
        bool $required,
    ): array {
        try {
            // Validate whether delivery methods are the proper type
            $shippingMethod = $dto->getShippingMethodId() instanceof Missing ? null :
                ShippingMethod::whereNot('shipping_type', ShippingType::DIGITAL)
                    ->findOrFail($dto->getShippingMethodId());

            $digitalShippingMethod = $dto->getDigitalShippingMethodId() instanceof Missing ? null :
                ShippingMethod::where('shipping_type', ShippingType::DIGITAL)
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

        if (!isset($exsistAddress)) {
            return false;
        }

        return true;
    }

    private function modifyAddress(Order $order, string $attribute, AddressDto|Missing $addressDto): ?Address
    {
        if ($addressDto instanceof Missing || !$this->checkAddress($addressDto)) {
            return null;
        }

        $old = Address::find($order->$attribute);
        Cache::add('address.' . $order->$attribute, $old ? ((string) $old) : null);
        $order->forceAudit($attribute);
        if ($attribute === 'shipping_address_id' && $order->shipping_type === ShippingType::POINT) {
            return Address::create($addressDto->toArray());
        }

        return Address::updateOrCreate(['id' => $order->$attribute], $addressDto->toArray());
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
        Address|string|null|Missing $shippingPlace,
        string $shippingType,
        Order $order
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
        Address|string|null|Missing $shippingPlace,
        string $shippingType,
        Order $order
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
