<?php

namespace App\Http\Controllers;

use App\Dtos\CartDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderIndexDto;
use App\Dtos\OrderProductSearchDto;
use App\Dtos\OrderProductUpdateDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Events\AddOrderDocument;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderUpdatedStatus;
use App\Events\RemoveOrderDocument;
use App\Events\SendOrderDocument;
use App\Exceptions\ClientException;
use App\Http\Requests\CartRequest;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderDocumentRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\OrderProductSearchRequest;
use App\Http\Requests\OrderProductUpdateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Requests\OrderUpdateStatusRequest;
use App\Http\Requests\SendDocumentRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderDocumentResource;
use App\Http\Resources\OrderProductResource;
use App\Http\Resources\OrderProductResourcePublic;
use App\Http\Resources\OrderPublicResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderProduct;
use App\Models\Status;
use App\Services\Contracts\DepositServiceContract;
use App\Services\Contracts\DocumentServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        private OrderServiceContract $orderService,
        private DocumentServiceContract $documentService,
        private DepositServiceContract $depositService
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
                'documents',
            ]);

        return OrderResource::collection(
            $query->paginate(Config::get('pagination.per_page')),
        );
    }

    public function show(Order $order): JsonResource
    {
        $order->load([
            'shippingMethod.priceRanges.prices',
            // Order products product
            'products.product',
            'products.product.metadata',
            'products.product.metadataPrivate',
            'products.product.media',
            'products.product.media.metadata',
            'products.product.media.metadataPrivate',
            'products.product.tags',
            'products.product.sales',
            'products.product.sales.orders',
            'products.product.sales.conditionGroups',
            'products.product.sales.conditionGroups.conditions',
            'products.product.sales.conditionGroups.conditions.users',
            'products.product.sales.conditionGroups.conditions.roles',
            'products.product.sales.conditionGroups.conditions.products',
            'products.product.sales.conditionGroups.conditions.productSets',
            'products.product.sales.products',
            'products.product.sales.productSets',
            'products.product.sales.productSets.children',
            'products.product.sales.productSets.childrenPublic',
            'products.product.sales.shippingMethods',
            'products.product.sales.shippingMethods.paymentMethods',
            'products.product.sales.shippingMethods.countries',
            'products.product.sales.shippingMethods.priceRanges',
            'products.product.sales.shippingMethods.priceRanges.prices',
            'products.product.sales.shippingMethods.metadata',
            'products.product.sales.shippingMethods.metadataPrivate',
            'products.product.sales.metadata',
            'products.product.sales.metadataPrivate',
            'products.product.items',
            'products.product.schemas',
            'products.product.schemas.usedSchemas',
            'products.product.schemas.metadata',
            'products.product.schemas.metadataPrivate',
            'products.product.sets',
            'products.product.sets.media',
            'products.product.sets.children',
            'products.product.sets.childrenPublic',
            'products.product.sets.metadata',
            'products.product.sets.metadataPrivate',
            'products.product.attributes',
            'products.product.attributes.metadata',
            'products.product.attributes.metadataPrivate',
            'products.product.seo',
            'products.product.seo.media',
            'products.product.productAvailabilities',
            'products.schemas',
            'products.deposits',
            'products.deposits.order',
            'products.deposits.order.status',
            'products.deposits.order.metadata',
            'products.deposits.order.metadataPrivate',
            'products.discounts',
            'products.discounts.orders',
            'products.discounts.conditionGroups',
            'products.discounts.conditionGroups.conditions',
            'products.discounts.conditionGroups.conditions.users',
            'products.discounts.conditionGroups.conditions.roles',
            'products.discounts.conditionGroups.conditions.products',
            'products.discounts.conditionGroups.conditions.productSets',
            'products.discounts.products',
            'products.discounts.productSets',
            'products.discounts.productSets.children',
            'products.discounts.productSets.childrenPublic',
            'products.discounts.shippingMethods',
            'products.discounts.shippingMethods.paymentMethods',
            'products.discounts.shippingMethods.countries',
            'products.discounts.shippingMethods.priceRanges',
            'products.discounts.shippingMethods.priceRanges.prices',
            'products.discounts.metadata',
            'products.discounts.metadataPrivate',
            // Discounts
            'discounts.orders',
            'discounts.conditionGroups',
            'discounts.conditionGroups.conditions',
            'discounts.conditionGroups.conditions.users',
            'discounts.conditionGroups.conditions.roles',
            'discounts.conditionGroups.conditions.products',
            'discounts.conditionGroups.conditions.productSets',
            'discounts.products',
            'discounts.productSets',
            'discounts.productSets.children',
            'discounts.productSets.childrenPublic',
            'discounts.shippingMethods',
            'discounts.shippingMethods.paymentMethods',
            'discounts.shippingMethods.countries',
            'discounts.shippingMethods.priceRanges',
            'discounts.shippingMethods.priceRanges.prices',
            'discounts.metadata',
            'discounts.metadataPrivate',
        ]);
        return OrderResource::make($order);
    }

    public function showPublic(Order $order): JsonResource
    {
        return OrderPublicResource::make($order);
    }

    public function store(OrderCreateRequest $request): JsonResource
    {
        return OrderPublicResource::make(
            $this->orderService->store(OrderDto::instantiateFromRequest($request)),
        );
    }

    public function updateStatus(OrderUpdateStatusRequest $request, Order $order): JsonResponse
    {
        if ($order->status && $order->status->cancel) {
            throw new ClientException(Exceptions::CLIENT_CHANGE_CANCELED_ORDER_STATUS);
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
                $deposit->item->update($this->depositService->getShippingTimeDateForQuantity($item));
                ItemUpdatedQuantity::dispatch($item);
            }
        }

        OrderUpdatedStatus::dispatch($order);

        return OrderResource::make($order)->response();
    }

    public function update(OrderUpdateRequest $request, Order $order): JsonResponse
    {
        $orderUpdateDto = OrderDto::instantiateFromRequest($request);

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
        $document = $this->documentService
            ->storeDocument(
                $order,
                $request->input('name'),
                $request->input('type'),
                $request->file('file'),
            );
        AddOrderDocument::dispatch($order, $document);

        return OrderDocumentResource::make($document);
    }

    public function deleteDocument(Order $order, OrderDocument $document): JsonResponse
    {
        $document = $this->documentService->removeDocument($order, $document->media_id);
        RemoveOrderDocument::dispatch($order, $document);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function downloadDocument(Order $order, OrderDocument $document): StreamedResponse
    {
        return $this->documentService->downloadDocument($document);
    }

    public function sendDocuments(SendDocumentRequest $request, Order $order): JsonResponse
    {
        $documents = OrderDocument::findMany($request->input('uuid'));
        //MAIL MICROSERVICE
        SendOrderDocument::dispatch($order, $documents);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function cartProcess(CartRequest $request): JsonResource
    {
        return CartResource::make($this->orderService->cartProcess(CartDto::instantiateFromRequest($request)));
    }

    public function updateOrderProduct(
        OrderProductUpdateRequest $request,
        Order $order,
        OrderProduct $product,
    ): JsonResource {
        return OrderProductResource::make($this->orderService->processOrderProductUrls(
            OrderProductUpdateDto::instantiateFromRequest($request),
            $product,
        ));
    }

    public function myOrderProducts(OrderProductSearchRequest $request): JsonResource
    {
        return OrderProductResourcePublic::collection(
            $this->orderService->indexMyOrderProducts(OrderProductSearchDto::instantiateFromRequest($request))
        );
    }

    public function sendUrls(Order $order): JsonResponse
    {
        $this->orderService->sendUrls($order);

        return Response::json(null, JsonResponse::HTTP_OK);
    }
}
