<?php

namespace App\Http\Controllers;

use App\Dtos\CartDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderIndexDto;
use App\Dtos\OrderProductSearchDto;
use App\Dtos\OrderProductUpdateDto;
use App\Dtos\OrderUpdateDto;
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
use App\Services\Contracts\DocumentServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceContract $orderService,
        private readonly DocumentServiceContract $documentService,
    ) {}

    public function index(OrderIndexRequest $request): JsonResource
    {
        $search_data = !$request->has('status_id')
            ? $request->validated() + ['status.hidden' => 0] : $request->validated();

        $query = Order::searchByCriteria($search_data)
            ->sort($request->input('sort', 'created_at:desc'))
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
                'salesChannel',
            ]);

        return OrderResource::collection(
            $query->paginate(Config::get('pagination.per_page')),
        );
    }

    public function show(Order $order): JsonResource
    {
        if (Config::get('flags.eager_load_orders')) {
            $order->load([
                'discounts',
                'discounts.metadata',
                'discounts.amounts',
                'products',
                'products.discounts',
                'products.discounts.amounts',
                'products.discounts.metadata',
                'products.discounts.orderProducts',
                'products.discounts.orders',
                'products.urls',
                'products.schemas',
                'products.deposits',
                'products.deposits.item',
                'products.product',
                'products.product.media',
                'products.product.media.metadata',
                'products.product.tags',
                'products.product.publishedTags',
                'products.product.metadata',
                'products.product.productAttributes',
                'products.product.productAttributes.options',
                'products.product.productAttributes.options.metadata',
                'products.product.sets',
                'products.product.sets.metadata',
                'products.product.sets.media',
                'products.product.sets.media.metadata',
                'products.product.sets.children',
                'products.product.sets.childrenPublic',
                'products.product.pricesBase',
                'products.product.pricesMin',
                'products.product.pricesMax',
                'products.product.pricesMinInitial',
                'products.product.pricesMaxInitial',
                'products.product.items',
                'products.product.schemas',
                'products.product.schemas.options',
                'products.product.schemas.prices',
                'products.product.schemas.usedSchemas',
                'products.product.schemas.options.items',
                'products.product.schemas.options.items.deposits',
                'products.product.schemas.options.metadata',
                'products.product.schemas.options.prices',
                'products.product.schemas.metadata',
                'products.product.sales',
                'products.product.sales.amounts',
                'products.product.sales.metadata',
                'products.product.sales.orderProducts',
                'products.product.attachments',
                'products.product.relatedSets',
                'products.product.relatedSets.metadata',
                'products.product.relatedSets.media',
                'products.product.relatedSets.media.metadata',
                'products.product.relatedSets.children',
                'products.product.relatedSets.childrenPublic',
                'products.product.banner',
                'products.product.banner.media',
                'products.product.seo',
                'products.product.pages',
                'products.product.pages.metadata',
            ]);
        }

        return OrderResource::make($order);
    }

    public function showPublic(Order $order): JsonResource
    {
        return OrderPublicResource::make($order);
    }

    public function store(OrderCreateRequest $request): JsonResource
    {
        return OrderResource::make(
            $this->orderService->store(OrderDto::instantiateFromRequest($request)),
        );
    }

    /**
     * @throws ClientException
     */
    public function updateStatus(OrderUpdateStatusRequest $request, Order $order): JsonResponse
    {
        if ($order->status && $order->status->cancel) {
            throw new ClientException(Exceptions::CLIENT_CHANGE_CANCELED_ORDER_STATUS);
        }

        $status = Status::query()->find($request->input('status_id'));
        if (!($status instanceof Status)) {
            throw new ClientException(Exceptions::CLIENT_UNKNOWN_STATUS);
        }

        $changed = false;

        if ($order->status_id !== $status->getKey()) {
            $order->update([
                'status_id' => $status->getKey(),
            ]);
            $changed = true;
        }

        if ($status->cancel) {
            $deposits = $order->deposits()->with('item')->get();
            $order->deposits()->delete();
            foreach ($deposits as $deposit) {
                if ($deposit->item !== null) {
                    ItemUpdatedQuantity::dispatch($deposit->item);
                }
            }
        }

        if ($changed) {
            OrderUpdatedStatus::dispatch($order);
        }

        return Response::json([], 204);
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
            $this->orderService->indexUserOrder(OrderIndexDto::instantiateFromRequest($request)),
        );
    }

    public function showUserOrder(Order $order): JsonResource
    {
        Gate::inspect('showUserOrder', [Order::class, $order]);

        return OrderResource::make($order);
    }

    public function storeDocument(OrderDocumentRequest $request, Order $order): JsonResource
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $document = $this->documentService
            ->storeDocument(
                $order,
                $request->input('name'),
                $request->input('type'),
                $file,
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
        // MAIL MICROSERVICE
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
            $this->orderService->indexMyOrderProducts(OrderProductSearchDto::instantiateFromRequest($request)),
        );
    }

    public function sendUrls(Order $order): JsonResponse
    {
        $this->orderService->sendUrls($order);

        return Response::json(null, JsonResponse::HTTP_OK);
    }
}
