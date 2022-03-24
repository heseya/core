<?php

namespace App\Http\Controllers;

use App\Dtos\OrderIndexDto;
use App\Dtos\OrderUpdateDto;
use App\Enums\SchemaType;
use App\Events\AddOrderDocument;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Events\OrderUpdatedStatus;
use App\Events\RemoveOrderDocument;
use App\Exceptions\OrderException;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderDocumentRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\OrderItemsRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Requests\OrderUpdateStatusRequest;
use App\Http\Requests\SendDocumentRequest;
use App\Http\Resources\OrderDocumentResource;
use App\Http\Resources\OrderPublicResource;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\DocumentServiceContract;
use App\Services\Contracts\ItemServiceContract;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private NameServiceContract $nameService,
        private OrderServiceContract $orderService,
        private DiscountServiceContract $discountService,
        private ItemServiceContract $itemService,
        private DocumentServiceContract $documentService
    ) {
    }

    public function index(OrderIndexRequest $request): JsonResource
    {
        $search_data = !$request->has('status_id')
            ? $request->validated() + ['status.hidden' => 0] : $request->validated();

        $query = Order::search($search_data)
            ->sort($request->input('sort'))
            ->with(['products', 'discounts', 'payments', 'documents']);

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
        # Schema values and warehouse items validation
        $items = [];

        foreach ($request->input('items', []) as $item) {
            $product = Product::findOrFail($item['product_id']);
            $schemas = $item['schemas'] ?? [];

            /** @var Schema $schema */
            foreach ($product->schemas as $schema) {
                $value = $schemas[$schema->getKey()] ?? null;

                $schema->validate($value, $item['quantity']);

                if ($value === null) {
                    continue;
                }

                $schemaItems = $schema->getItems($value, $item['quantity']);
                $items = $this->itemService->addItemArrays($items, $schemaItems);
            }
        }

        $this->itemService->validateItems($items);

        # Discount validation
        foreach ($request->input('discounts', []) as $discount) {
            Discount::where('code', $discount)->firstOrFail();
        }

        # Creating order
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
            'user_id' => Auth::user()->getKey(),
            'user_type' => Auth::user()::class,
        ]);

        # Add products to order
        $summary = 0;

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
                $summary += $product->price * $item['quantity'];

                # Add schemas to products
                foreach ($product->schemas as $schema) {
                    $value = $schemas[$schema->getKey()] ?? null;

                    if ($value === null) {
                        continue;
                    }

                    $price = $schema->getPrice($value, $schemas);

                    if ($schema->type->is(SchemaType::SELECT)) {
                        $option = $schema->options()->findOrFail($value);
                        $value = $option->name;

                        # Remove items from warehouse
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

                    $summary += $price;
                }
            }
        } catch (Throwable $exception) {
            $order->delete();

            throw $exception;
        }

        # Apply discounts to order
        $discounts = [];
        $cartValue = $summary;
        foreach ($request->input('discounts', []) as $discount) {
            $discount = Discount::where('code', $discount)->first();

            $discounts[$discount->getKey()] = [
                'type' => $discount->type,
                'discount' => $discount->discount,
            ];

            $summary -= $this->discountService->calc($cartValue, $discount);
        }
        $order->discounts()->sync($discounts);

        # Calculate shipping price for complete order
        $summary = max($summary, 0);
        $shippingPrice = $shippingMethod->getPrice($summary);
        $summary = round($summary + $shippingPrice, 2);

        $order->update([
            'shipping_price' => $shippingPrice,
            'summary' => $summary,
            'paid' => $summary <= 0,
        ]);

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

    public function storeDocument(OrderDocumentRequest $request, Order $order): JsonResource
    {
        $document = $this->documentService->storeDocument($order, $request->only('name', 'type', 'file'));
        AddOrderDocument::dispatch($document);

        return OrderDocumentResource::collection($order->documents);
    }

    public function deleteDocument(Order $order, OrderDocument $document): JsonResponse
    {
        $document = $this->documentService->removeDocument($order, $document->media_id);
        RemoveOrderDocument::dispatch($document);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function sendDocuments(SendDocumentRequest $request, Order $order): JsonResponse
    {
        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
