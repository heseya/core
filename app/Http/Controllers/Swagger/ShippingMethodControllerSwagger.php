<?php

namespace App\Http\Controllers\Swagger;

use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface ShippingMethodControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/shipping-methods",
     *   summary="list shipping methods",
     *   tags={"Shipping"},
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
    public function index(): JsonResource;

    /**
     * @OA\Post(
     *   path="/shipping-methods",
     *   summary="add new shipping method",
     *   tags={"Shipping"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/ShippingMethod",
     *     ),
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
    public function store(Request $request);

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
     *       type="integer",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/ShippingMethod",
     *     ),
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
    public function update(ShippingMethod $shipping_method, Request $request): JsonResource;

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
     *       type="integer",
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
    public function destroy(ShippingMethod $shipping_method);
}
