<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\Payments\PaymentStoreRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface PaymentControllerSwagger
{
    /**
     * @OA\Post(
     *   path="/orders/{code}/pay/{payment_method}",
     *   summary="redirect to payment",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="code",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="payment_method",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     request="OrderCreate",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="continue_url",
     *         type="string",
     *         description="URL that the buyer will be redirected to, after making payment",
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Payment",
     *       )
     *     )
     *   )
     * )
     */
    public function store(Order $order, string $method, PaymentStoreRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/payments/{payment_method}",
     *   summary="Update payment status by payment provider",
     *   tags={"Payments"},
     *   @OA\Parameter(
     *     name="payment_method",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   )
     * )
     *
     * @return mixed
     */
    public function update(string $method, Request $request);
}
