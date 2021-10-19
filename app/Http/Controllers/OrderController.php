<?php

namespace App\Http\Controllers;

use App\Dtos\OrderUpdateDto;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Events\OrderUpdatedStatus;
use App\Exceptions\OrderException;
use App\Http\Controllers\Swagger\OrderControllerSwagger;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\OrderItemsRequest;
use App\Http\Requests\OrderSyncRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Requests\OrderUpdateStatusRequest;
use App\Http\Resources\OrderPublicResource;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Throwable;

class OrderController extends Controller implements OrderControllerSwagger
{
    private NameServiceContract $nameService;
    private OrderServiceContract $orderService;

    public function __construct(NameServiceContract $nameService, OrderServiceContract $orderService)
    {
        $this->nameService = $nameService;
        $this->orderService = $orderService;
    }

    public function index(OrderIndexRequest $request): JsonResource
    {
        $query = Order::search($request->validated())
            ->sort($request->input('sort'));

        return OrderResource::collection(
            $query->paginate(Config::get('pagination.per_page')),
        );
    }

    public function show(Order $order): JsonResource
    {
        return OrderResource::make($order);
    }

    public function showPublic(Order $order): JsonResource
    {
        return OrderPublicResource::make($order);
    }

    public function store(OrderCreateRequest $request): JsonResource
    {
        $validated = $request->validated();

        $shippingMethod = ShippingMethod::findOrFail($request->input('shipping_method_id'));
        $deliveryAddress = Address::firstOrCreate($validated['delivery_address']);

        if ($request->filled('invoice_address.name')) {
            $invoiceAddress = Address::firstOrCreate($validated['invoice_address']);
        }

        $order = Order::create([
            'code' => $this->nameService->generate(),
            'email' => $request->input('email'),
            'comment' => $request->input('comment'),
            'currency' => 'PLN',
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_price' => 0.0,
            'status_id' => Status::select('id')->orderBy('order')->first()->getKey(),
            'delivery_address_id' => $deliveryAddress->getKey(),
            'invoice_address_id' => isset($invoiceAddress) ? $invoiceAddress->getKey() : null,
        ]);

        try {
            foreach ($request->input('items', []) as $item) {
                $product = Product::findOrFail($item['product_id']);
                $schemas = $item['schemas'] ?? [];

                $orderProduct = new OrderProduct([
                    'product_id' => $product->getKey(),
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $order->products()->save($orderProduct);

                foreach ($product->schemas as $schema) {
                    $schema->validate(
                        $schemas[$schema->getKey()] ?? null,
                        $item['quantity'],
                    );

                    $value = $schemas[$schema->getKey()] ?? null;
                    $price = $schema->getPrice($value, $schemas);

                    if ($schema->type === 4) {
                        $option = $schema->options()->findOrFail($value);
                        $value = $option->name;

                        foreach ($option->items as $optionItem) {
                            $orderProduct->deposits()->create([
                                'item_id' => $optionItem->getKey(),
                                'quantity' => -1 * $item['quantity'],
                            ]);
                            ItemUpdatedQuantity::dispatch($optionItem);
                        }
                    }

                    $orderProduct->schemas()->create([
                        'name' => $schema->name,
                        'value' => $value,
                        'price' => $price,
                    ]);
                }
            }

            $discounts = [];
            foreach ($request->input('discounts', []) as $discount) {
                $discount = Discount::where('code', $discount)->firstOrFail();
                $discounts[$discount->getKey()] = [
                    'type' => $discount->type,
                    'discount' => $discount->discount,
                ];
            }
            $order->discounts()->sync($discounts);

            $order->update([
                'shipping_price' => $shippingMethod->getPrice($order->summary),
            ]);
        } catch (Throwable $exception) {
            $order->delete();

            throw $exception;
        }

        OrderCreated::dispatch($order);

        return OrderPublicResource::make($order);
    }

    public function sync(OrderSyncRequest $request): JsonResponse
    {
        foreach ($request->input('items', []) as $item) {
            Product::findOrFail($item['product_id']);
        }

        $deliveryAddress = Address::firstOrCreate($request->input('delivery_address'));

        if ($request->filled('invoice_address.name')) {
            $invoiceAddress = Address::firstOrCreate($request->input('invoice_address'));
        }

        $order = Order::updateOrCreate(['code' => $request->input('code')], [
            'email' => $request->input('email'),
            'comment' => $request->input('comment'),
            'currency' => 'PLN',
            'shipping_method_id' => $request->input('shipping_method_id'),
            'shipping_price' => $request->input('shipping_price'),
            'shipping_number' => $request->input('shipping_number'),
            'status_id' => $request->input('status_id'),
            'delivery_address_id' => $deliveryAddress->getKey(),
            'invoice_address_id' => isset($invoiceAddress) ? $invoiceAddress->getKey() : null,
            'created_at' => $request->input('created_at'),
        ]);

        $order->products()->delete();

        foreach ($request->input('items', []) as $item) {
            $product = Product::findOrFail($item['product_id']);

            $order_product = new OrderProduct([
                'product_id' => $product->getKey(),
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);

            $order->products()->save($order_product);

            foreach ($item['schemas'] ?? [] as $schema) {
                $order_product->schemas()->create([
                    'name' => $schema['name'],
                    'value' => $schema['value'],
                    'price' => $schema['price'],
                ]);
            }
        }

        $order->payments()->delete();

        foreach ($request->input('payments') as $payment) {
            $order->payments()->create([
                'method' => $payment['name'],
                'amount' => $payment['amount'],
                'payed' => $payment['payed'],
                'created_at' => $request->input('created_at'),
            ]);
        }

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function verify(OrderItemsRequest $request): JsonResponse
    {
        foreach ($request->input('items', []) as $item) {
            $product = Product::findOrFail($item['product_id']);
            $schemas = $item['schemas'] ?? [];

            foreach ($product->schemas as $schema) {
                $schema->validate(
                    $schemas[$schema->getKey()] ?? null,
                    $item['quantity'],
                );
            }
        }

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function updateStatus(OrderUpdateStatusRequest $request, Order $order): JsonResponse
    {
        if ($order->status->cancel) {
            throw new OrderException(__('admin.error.order_change_status_canceled'));
        }

        $status = Status::findOrFail($request->input('status_id'));
        $order->update([
            'status_id' => $status->getKey(),
        ]);

        if ($status->cancel) {
            $deposits = $order->deposits()->with('item')->get();
            $order->deposits()->delete();
            foreach ($deposits as $deposit)
            {
                ItemUpdatedQuantity::dispatch($deposit->item);
            }
        }

        OrderUpdatedStatus::dispatch($order);

        return OrderResource::make($order)->response();
    }

    public function update(OrderUpdateRequest $request, Order $order): JsonResponse
    {
        $orderUpdateDto = OrderUpdateDto::instantiateFromRequest($request);

        return $this->orderService->update($orderUpdateDto, $order);
    }
}
