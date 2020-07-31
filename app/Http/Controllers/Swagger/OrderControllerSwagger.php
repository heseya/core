<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\OrderCreateRequest;
use App\Models\Order;
use Illuminate\Http\Request;
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
    public function index(Request $request): JsonResource;

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
    public function store(OrderCreateRequest $request);

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
     *             property="cartitem_id",
     *             type="string",
     *           ),
     *           @OA\Property(
     *             property="product_id",
     *             type="integer",
     *           ),
     *           @OA\Property(
     *             property="quantity",
     *             type="number",
     *           ),
     *           @OA\Property(
     *             property="schema_items",
     *             type="array",
     *             @OA\Items(
     *               type="integer"
     *             )
     *           ),
     *           @OA\Property(
     *             property="custom_schemas",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(
     *                 property="schema_id",
     *                 type="integer",
     *               ),
     *               @OA\Property(
     *                 property="value",
     *                 type="string",
     *               )
     *             )
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(
     *             property="cartitem_id",
     *             type="string",
     *           ),
     *           @OA\Property(
     *             property="enough",
     *             type="boolean",
     *           )
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function verify(Request $request);

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
     *       type="id",
     *       example="2",
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
     *     response=204,
     *     description="Success",
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function updateStatus(Order $order, Request $request);
}
