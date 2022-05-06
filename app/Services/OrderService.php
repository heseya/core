<?php

namespace App\Services;

use App\Dtos\AddressDto;
use App\Dtos\CartDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderIndexDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SchemaType;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Exceptions\ClientException;
use App\Exceptions\ServerException;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\CartResource;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\ItemServiceContract;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Exception;
use Heseya\Dto\Missing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
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

        $value = ($value < 0 ? 0 : $value) + $order->shipping_price;

        return round($value, 2);
    }

    public function store(OrderDto $dto): Order
    {
        DB::beginTransaction();
        # Schema values and warehouse items validation
        $products = $this->itemService->checkOrderItems($dto->getItems());

        # Creating order
        $shippingMethod = ShippingMethod::findOrFail($dto->getShippingMethodId());
        $deliveryAddress = Address::firstOrCreate($dto->getDeliveryAddress()->toArray());

        if ($this->checkAddress($dto->getInvoiceAddress())) {
            $invoiceAddress = Address::firstOrCreate($dto->getInvoiceAddress()->toArray());
        }

        $status = Status::select('id')->orderBy('order')->first();

        if ($status === null) {
            throw new ServerException(Exceptions::SERVER_ORDER_STATUSES_NOT_CONFIGURED);
        }

        $order = Order::create(
            $dto->toArray() + [
                'code' => $this->nameService->generate(),
                'currency' => 'PLN',
                'shipping_price_initial' => 0.0,
                'shipping_price' => 0.0,
                'cart_total_initial' => 0.0,
                'cart_total' => 0.0,
                'status_id' => $status->getKey(),
                'delivery_address_id' => $deliveryAddress->getKey(),
                'invoice_address_id' => isset($invoiceAddress) ? $invoiceAddress->getKey() : null,
                'buyer_id' => Auth::user()->getKey(),
                'buyer_type' => Auth::user()::class,
            ]
        );

        # Add products to order
        $cartValueInitial = 0;

        try {
            foreach ($dto->getItems() as $item) {
                $product = $products->firstWhere('id', $item->getProductId());

                $orderProduct = new OrderProduct([
                    'product_id' => $item->getProductId(),
                    'quantity' => $item->getQuantity(),
                    'price_initial' => $product->price,
                    'price' => $product->price,
                    'base_price_initial' => $product->price,
                    'base_price' => $product->price,
                    'name' => $product->name,
                ]);

                $order->products()->save($orderProduct);
                $cartValueInitial += $product->price * $item->getQuantity();

                $schemaProductPrice = 0;
                # Add schemas to products
                foreach ($item->getSchemas() as $schemaId => $value) {
                    $schema = $product->schemas()->findOrFail($schemaId);

                    $price = $schema->getPrice($value, $item->getSchemas());

                    if ($schema->type->is(SchemaType::SELECT)) {
                        $option = $schema->options()->findOrFail($value);
                        $value = $option->name;

                        # Remove items from warehouse
                        foreach ($option->items as $optionItem) {
                            $orderProduct->deposits()->create([
                                'item_id' => $optionItem->getKey(),
                                'quantity' => -1 * $item->getQuantity(),
                            ]);
                            ItemUpdatedQuantity::dispatch($optionItem);
                        }
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
        } catch (Throwable $exception) {
            $order->delete();

            throw $exception;
        }

        $shippingPrice = $shippingMethod->getPrice($cartValueInitial);

        $order->update([
            'cart_total_initial' => $cartValueInitial,
            'cart_total' => $cartValueInitial,
            'shipping_price_initial' => $shippingPrice,
            'shipping_price' => $shippingPrice,
        ]);

        # Apply discounts to order
        $order = $this->discountService->calcOrderDiscounts($order, $dto);
        $order->push();

        DB::commit();
        OrderCreated::dispatch($order);

        return $order;
    }

    public function update(OrderDto $dto, Order $order): JsonResponse
    {
        DB::beginTransaction();

        try {
            $deliveryAddress = $this->modifyAddress(
                $order,
                'delivery_address_id',
                $dto->getDeliveryAddress(),
            );

            $invoiceAddress = $this->modifyAddress(
                $order,
                'invoice_address_id',
                $dto->getInvoiceAddress(),
            );

            $deliveryAddressId = $deliveryAddress instanceof Address
                ? ['delivery_address_id' => $deliveryAddress->getKey() ]
                : (!$dto->getDeliveryAddress() instanceof Missing ? ['delivery_address_id' => null] : []);

            $invoiceAddressId = $invoiceAddress instanceof Address
                ? ['invoice_address_id' => $invoiceAddress->getKey() ]
                : (!$dto->getInvoiceAddress() instanceof Missing ? ['invoice_address_id' => null] : []);

            $order->update($dto->toArray() + $deliveryAddressId + $invoiceAddressId);

            DB::commit();

            OrderUpdated::dispatch($order);

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
                'deliveryAddress',
                'metadata',
                'documents',
            ])
            ->paginate(Config::get('pagination.per_page'));
    }

    public function cartProcess(CartDto $cartDto): CartResource
    {
        // Lista tylko dostępnych produktów
        $products = $this->itemService->checkCartItems($cartDto->getItems());

        return $this->discountService->calcCartDiscounts($cartDto, $products);
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
        if (!$this->checkAddress($addressDto)) {
            return null;
        }

        $old = Address::find($order->$attribute);
        Cache::add('address.' . $order->$attribute, $old ? ((string) $old) : null);
        $order->forceAudit($attribute);
        return Address::updateOrCreate(['id' => $order->$attribute], $addressDto->toArray());
    }
}
