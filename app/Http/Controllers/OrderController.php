<?php

namespace App\Http\Controllers;

use App\Dtos\CartDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderIndexDto;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderUpdatedStatus;
use App\Exceptions\OrderException;
use App\Http\Requests\CartRequest;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\OrderItemsRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Requests\OrderUpdateStatusRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderPublicResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Status;
use App\Services\Contracts\OrderServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class OrderController extends Controller
{
    public function __construct(
        private OrderServiceContract $orderService,
    ) {
    }

    public function index(OrderIndexRequest $request): JsonResource
    {
        $search_data = !$request->has('status_id')
            ? $request->validated() + ['status.hidden' => 0] : $request->validated();

        $query = Order::searchByCriteria($search_data)
            ->sort($request->input('sort'))
            ->with([
                'products',
                'discounts',
                'payments',
                'status',
                'shippingMethod',
                'shippingMethod.paymentMethods',
                'deliveryAddress',
                'metadata',
            ]);

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
        return OrderPublicResource::make($this->orderService->store(OrderDto::fromFormRequest($request)));
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

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
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
                $item = $deposit->item;
                $item->decrement('quantity', $deposit->quantity);
                ItemUpdatedQuantity::dispatch($item);
            }
        }

        OrderUpdatedStatus::dispatch($order);

        return OrderResource::make($order)->response();
    }

    public function update(OrderUpdateRequest $request, Order $order): JsonResponse
    {
        $orderUpdateDto = OrderDto::fromFormRequest($request);

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

    public function cartProcess(CartRequest $request): JsonResource
    {
        return CartResource::make($this->orderService->cartProcess(CartDto::fromFormRequest($request)));
    }
}
