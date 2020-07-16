<?php

namespace App\Http\Controllers\Swagger;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface PaymentMethodControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/payment-methods",
     *   summary="list payment methods",
     *   tags={"Payments"},
     *   @OA\Parameter(
     *     name="shipping_method_id",
     *     in="query",
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/PaymentMethod"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/payment-methods",
     *   summary="add new payment method",
     *   tags={"Payments"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/PaymentMethod",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/PaymentMethod",
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
     *   path="/payment-methods/id:{id}",
     *   summary="update payment method",
     *   tags={"Payments"},
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
     *       ref="#/components/schemas/PaymentMethod",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/PaymentMethod",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(PaymentMethod $payment_method, Request $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/payment-methods/id:{id}",
     *   summary="delete payment method",
     *   tags={"Payments"},
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
    public function destroy(PaymentMethod $payment_method);
}
