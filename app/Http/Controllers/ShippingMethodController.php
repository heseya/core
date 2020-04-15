<?php

namespace App\Http\Controllers;

use App\ShippingMethod;
use Illuminate\Http\Request;
use App\Http\Resources\ShippingMethodResource;
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
}
