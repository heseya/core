<?php

namespace App\Http\Controllers;

use App\Dtos\OrderIndexDto;
use App\Dtos\OrderUpdateDto;
use App\Enums\SchemaType;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Events\OrderUpdatedStatus;
use App\Exceptions\OrderException;
use App\Http\Controllers\Swagger\OrderControllerSwagger;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\OrderItemsRequest;
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
use App\Models\User;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
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
            ->sort($request->input('sort'))
            ->with(['products', 'discounts', 'payments']);

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
            'user_id' => Auth::user() instanceof User ? Auth::user()->getKey() : null,
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
                    $value = $schemas[$schema->getKey()] ?? null;

                    $schema->validate($value, $item['quantity']);

                    if ($value === null) {
                        continue;
                    }

                    $price = $schema->getPrice($value, $schemas);

                    if ($schema->type->is(SchemaType::SELECT)) {
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
        if ($order->status && $order->status->cancel) {
            throw new OrderException(__('admin.error.order_change_status_canceled'));
        }

        $status = Status::findOrFail($request->input('status_id'));
        $order->update([
            'status_id' => $status->getKey(),
        ]);

        if ($status->cancel) {
            $deposits = $order->deposits()->with('item')->get();
            $order->deposits()->delete();
            foreach ($deposits as $deposit) {
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

    public function indexUserOrder(OrderIndexRequest $request): JsonResource
    {
        Gate::inspect('indexUserOrder', [Order::class]);

        return OrderResource::collection(
            $this->orderService->indexUserOrder(OrderIndexDto::instantiateFromRequest($request))
        );
    }

    public function showUserOrder(Order $order): JsonResource
    {
        Gate::inspect('showUserOrder', [Order::class, $order]);

        return OrderResource::make($order);
    }
}
