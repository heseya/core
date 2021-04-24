<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Swagger\OrderControllerSwagger;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\OrderItemsRequest;
use App\Http\Requests\OrderSyncRequest;
use App\Http\Requests\OrderUpdateStatusRequest;
use App\Http\Resources\OrderPublicResource;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderSchema;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderController extends Controller implements OrderControllerSwagger
{
    public function index(OrderIndexRequest $request): JsonResource
    {
        $query = Order::search($request->validated())
            ->sort($request->input('sort'));

        return OrderResource::collection(
            $query->paginate((int) $request->input('limit', 15)),
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
        $products = [];
        $orderSchemas = [];

        foreach ($request->input('items', []) as $item) {
            $product = Product::findOrFail($item['product_id']);
            $schemas = $item['schemas'] ?? [];

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
                }

                $orderSchemas[$product->getKey()][] = new OrderSchema([
                    'name' => $schema->name,
                    'value' => $value,
                    'price' => $price,
                ]);
            }

            $products[] = new OrderProduct([
                'product_id' => $product->getKey(),
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);
        }

        $shippingMethod = ShippingMethod::findOrFail($request->input('shipping_method_id'));
        $deliveryAddress = Address::firstOrCreate($request->input('delivery_address'));

        if ($request->filled('invoice_address.name')) {
            $invoiceAddress = Address::firstOrCreate($request->input('invoice_address'));
        }

        $order = Order::create([
            'email' => $request->input('email'),
            'comment' => $request->input('comment'),
            'currency' => 'PLN',
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_price' => $shippingMethod->price,
            'status_id' => Status::select('id')->orderBy('order')->first()->getKey(),
            'delivery_address_id' => $deliveryAddress->getKey(),
            'invoice_address_id' => isset($invoiceAddress) ? $invoiceAddress->getKey() : null,
        ]);

        $order->products()->saveMany($products);

        foreach ($order->products as $product) {
            $product->schemas()->saveMany($orderSchemas[$product->product_id] ?? []);
        }

        // logs
        $order->logs()->create([
            'content' => 'Utworzenie zamówienia.',
            'user' => 'API',
        ]);

        OrderCreated::dispatch($order);

        return OrderPublicResource::make($order);
    }

    public function sync(OrderSyncRequest $request): JsonResponse
    {
        $products = [];
        $orderSchemas = [];

        foreach ($request->input('items', []) as $item) {
            $product = Product::findOrFail($item['product_id']);

            foreach ($item['schemas'] ?? [] as $schema) {
                $orderSchemas[$product->getKey()][] = new OrderSchema([
                    'name' => $schema['name'],
                    'value' => $schema['value'],
                    'price' => $schema['price'],
                ]);
            }

            $products[] = new OrderProduct([
                'product_id' => $product->getKey(),
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
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
        $order->products()->saveMany($products);

        foreach ($order->products as $product) {
            $product->schemas()->delete();
            $product->schemas()->saveMany($orderSchemas[$product->product_id] ?? []);
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

        return response()->json(null, 204);
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

        return response()->json(null, 204);
    }

    public function updateStatus(OrderUpdateStatusRequest $request, Order $order): JsonResource
    {
        $order->update([
            'status_id' => $request->input('status_id'),
        ]);

        OrderStatusUpdated::dispatch($order);

        return OrderResource::make($order);
    }
}
