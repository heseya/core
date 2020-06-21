<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Exceptions\Error;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\PaymentResource;
use Throwable;

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
     *         ref="#/components/schemas/Payment",
     *       )
     *     )
     *   )
     * )
     *
     * @param Order $order
     * @param string $method
     * @param Request $request
     *
     * @return PaymentResource|JsonResponse|object
     */
    public function pay(Order $order, string $method, Request $request)
    {
        if ($order->isPayed()) {
            return Error::abort('Order is already paid.', 406);
        }

        if (!array_key_exists($method, config('payable.aliases'))) {
            return Error::abort('Unknown payment method.', 400);
        }

        $method_class = config('payable.aliases')[$method];

        $payment = $order->payments()->create([
            'method' => $method,
            'amount' => $order->summary - $order->payed,
            'continue_url' => $request->continue ?? null,
            'currency' => 'PLN',
        ]);

        try {
            $payment->update($method_class::generateUrl($payment));
        } catch (Throwable $e) {
            return Error::abort('Cannot generate payment url.', 500);
        }

        return PaymentResource::make($payment);
    }

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
     * @param string $method
     * @param Request $request
     *
     * @return JsonResponse|object
     */
    public function receive(string $method, Request $request)
    {
        if (!array_key_exists($method, config('payable.aliases'))) {
            return Error::abort('Unknown payment method.', 400);
        }

        $method_class = config('payable.aliases')[$method];

        return $method_class::translateNotification($request);
    }
}
