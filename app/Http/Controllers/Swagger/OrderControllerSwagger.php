<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\OrderItemsRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Requests\OrderUpdateStatusRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface OrderControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/orders",
     *   summary="orders list",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Full text search.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Sorting string.",
     *     @OA\Schema(
     *       type="string",
     *       example="code:asc,created_at:desc,id"
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="status_id",
     *     in="query",
     *     description="Status UUID",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="shipping_method_id",
     *     in="query",
     *     description="Shipping Method UUID",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           ref="#/components/schemas/Order",
     *         )
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index(OrderIndexRequest $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/orders/id:{id}",
     *   summary="order view",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="code",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="D3PT88",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Order",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function show(Order $order): JsonResource;
    /**
     * @OA\Get(
     *   path="/orders/{code}",
     *   summary="public order view",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="code",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="D3PT88",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Order",
     *       )
     *     )
     *   )
     * )
     */
    public function showPublic(Order $order): JsonResource;

    /**
     * @OA\Post(
     *   path="/orders",
     *   summary="add new order",
     *   tags={"Orders"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/OrderCreate",
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Order",
     *       )
     *     )
     *   )
     * )
     */
    public function store(OrderCreateRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/orders/verify",
     *   summary="verify cart",
     *   tags={"Orders"},
     *   @OA\RequestBody(
     *     request="OrderCreate",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="items",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(
     *             property="product_id",
     *             type="string",
     *             example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *           ),
     *           @OA\Property(
     *             property="quantity",
     *             type="number",
     *           ),
     *           @OA\Property(
     *             property="schemas",
     *             type="object",
     *             @OA\Property(
     *               property="119c0a63-1ea1-4769-8d5f-169f68de5598",
     *               type="string",
     *               example="123459fb-39a4-4dd0-8240-14793aa1f73b",
     *             ),
     *             @OA\Property(
     *               property="02b97693-857c-4fb9-9999-47400ac5fbef",
     *               type="string",
     *               example="HE + YA",
     *             ),
     *           ),
     *         ),
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="Success",
     *   )
     * )
     */
    public function verify(OrderItemsRequest $request): JsonResponse;

    /**
     * @OA\Post(
     *   path="/orders/id:{id}/status",
     *   summary="change order status",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="1c8705ce-5fae-4468-b88a-8784cb5414a0",
     *     ),
     *   ),
     *   @OA\RequestBody(
     *     request="OrderCreate",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="status_id",
     *         type="integer",
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           ref="#/components/schemas/Order",
     *         )
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function updateStatus(OrderUpdateStatusRequest $request, Order $order): JsonResponse;

    /**
     * @OA\Patch(
     *   path="/orders/id:{order:id}",
     *   summary="update product",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     ),
     *   ),
     *   @OA\RequestBody(
     *     request="OrderUpdate",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="id",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="email",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="comment",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="shipping_number",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="shipping_price",
     *         type="float",
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Order"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(OrderUpdateRequest $request, Order $order): JsonResponse;
}
