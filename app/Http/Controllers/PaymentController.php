<?php

namespace App\Http\Controllers;

use Error;
use App\Order;
use App\Http\Requests\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderPublicResource;

class PaymentController extends Controller
{
    /**
     * @OA\Get(
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
     *   @OA\Parameter(
     *     name="continue",
     *     in="query",
     *     description="URL that the buyer will be redirected to, after making payment",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         @OA\Property(
     *           property="redirectUrl",
     *           type="string"
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function pay(Order $order, string $method, Request $request)
    {
        if (
            $order->payment_status !== 0 ||
            $order->shop_status === 3
        ) {
            return Error::abort('Order not payable.', 406);
        }

        if (!array_key_exists($method, config('payable.aliases'))) {
            return Error::abort('Unkown payment method.', 400);
        }

        $method_class = config('payable.aliases')[$method];

        $payment = $order->payments()->create([
            'method' => $method,
            'amount' => $order->summary,
            'continueUrl' => $request->continue ?? null,
            'currency' => 'PLN',
        ]);

        $payment->update($method_class::generateUrl($payment));

        return response()->json(['data' => [
            'redirectUrl' => $payment->redirectUrl,
        ]]);
    }
}
