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
use App\Services\Contracts\NameServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

class OrderController extends Controller implements OrderControllerSwagger
{
    private NameServiceContract $nameService;

    public function __construct(NameServiceContract $nameService)
    {
        $this->nameService = $nameService;
    }

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
        $shippingMethod = ShippingMethod::findOrFail($request->input('shipping_method_id'));
        $deliveryAddress = Address::firstOrCreate($request->input('delivery_address'));

        if ($request->filled('invoice_address.name')) {
            $invoiceAddress = Address::firstOrCreate($request->input('invoice_address'));
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
    
                $order_product = new OrderProduct([
                    'product_id' => $product->getKey(),
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);
    
                $order->products()->save($order_product);
    
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
    
                    $order_product->schemas()->create([
                        'name' => $schema->name,
                        'value' => $value,
                        'price' => $price,
                    ]);
                }
            }

            $order->update([
                'shipping_price' => $shippingMethod->getPrice($order->summary),
            ]);
        } catch (Throwable $e) {
            $order->delete();

            throw $e;
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
        foreach ($request->input('items', []) as $item) {
            $product = Product::findOrFail($item['product_id']);
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
