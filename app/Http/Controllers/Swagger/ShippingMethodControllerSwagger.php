<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\ShippingMethodIndexRequest;
use App\Http\Requests\ShippingMethodReorderRequest;
use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Models\ShippingMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface ShippingMethodControllerSwagger
{
    /**
     * @OA\Get (
     *   path="/shipping-methods",
     *   summary="list shipping methods by filters",
     *   tags={"Shipping"},
     *   @OA\Parameter(
     *     name="country",
     *     in="query",
     *     required=false,
     *     @OA\Schema(
     *       type="string",
     *       example="DE",
     *     )
     *   ),
     *     @OA\Parameter(
     *     name="cart_value",
     *     in="query",
     *     required=false,
     *     @OA\Schema(
     *       type="float",
     *       example=1200
     *     )
     *   ),
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ShippingMethodIndex",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/ShippingMethod"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(ShippingMethodIndexRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/shipping-methods",
     *   summary="add new shipping method",
     *   tags={"Shipping"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ShippingMethodStore",
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/ShippingMethod",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(ShippingMethodStoreRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/shipping-methods/id:{id}",
     *   summary="update shipping method",
     *   tags={"Shipping"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="d1365285-ad58-48da-88e2-99e9f4c0800d",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ShippingMethodUpdate",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/ShippingMethod",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(ShippingMethodUpdateRequest $request, ShippingMethod $shipping_method): JsonResource;

    /**
     * @OA\Post(
     *   path="/shipping-methods/order",
     *   deprecated=true,
     *   summary="Reorder shipping method",
     *   tags={"Shipping"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ShippingMethodReorder",
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

    /**
     * @OA\Post(
     *   path="/shipping-methods/reorder",
     *   summary="Reorder shipping method",
     *   tags={"Shipping"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ShippingMethodReorder",
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
    public function reorder(ShippingMethodReorderRequest $request): JsonResponse;

    /**
     * @OA\Delete(
     *   path="/shipping-methods/id:{id}",
     *   summary="delete shipping method",
     *   tags={"Shipping"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="d1365285-ad58-48da-88e2-99e9f4c0800d",
     *     )
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
    public function destroy(ShippingMethod $shippingMethod): JsonResponse;
}
