<?php

namespace App\Http\Controllers;

use App\ShippingMethod;
use Illuminate\Http\Request;
use App\Http\Resources\ShippingMethodResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ShippingMethodController extends Controller
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
    public function index(): ResourceCollection
    {
        return ShippingMethodResource::collection(
            ShippingMethod::where('public', true)->get(),
        );
    }

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
    public function create(Request $request): JsonResource
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'public' => 'boolean',
        ]);

        $shipping_method = ShippingMethod::create($request->all());

        return new ShippingMethodResource($shipping_method);
    }

    /**
     * @OA\Put(
     *   path="/shipping-methods/id:{id}",
     *   summary="update shipping method",
     *   tags={"Shipping"},
     *   @OA\Parameter(
     *     name="id",
     *     in="query",
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
    public function update(ShippingMethod $shipping_method, Request $request): JsonResource
    {
        $request->validate([
            'name' => 'string|max:255',
            'price' => 'numeric',
            'public' => 'boolean',
        ]);

        $shipping_method->update($request->all());

        return new ShippingMethodResource($shipping_method);
    }

    /**
     * @OA\Delete(
     *   path="/shipping-methods/id:{id}",
     *   summary="delete shipping method",
     *   tags={"Shipping"},
     *   @OA\Parameter(
     *     name="id",
     *     in="query",
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
    public function delete(ShippingMethod $shipping_method)
    {
        $shipping_method->delete();

        return response()->json(null, 204);
    }
}
