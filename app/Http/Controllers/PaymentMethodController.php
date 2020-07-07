<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    /**
     * @OA\Get(
     *   path="/payment-methods",
     *   summary="list payment methods",
     *   tags={"Payments"},
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
    public function index(?int $shippingMethod = null): JsonResource
    {
        if ($shippingMethod) {
            $shippingMethod = ShippingMethod::findOrFail($shippingMethod);
            $query = $shippingMethod->payments();
        } else {
            $query = PaymentMethod::select();
        }

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return PaymentMethodResource::collection($query->get());
    }

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
    public function create(Request $request): JsonResource
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'public' => 'boolean',
        ]);

        $payment_method = PaymentMethod::create($request->all());

        return PaymentMethodResource::make($payment_method);
    }

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
    public function update(PaymentMethod $payment_method, Request $request): JsonResource
    {
        $request->validate([
            'name' => 'string|max:255',
            'public' => 'boolean',
        ]);

        $payment_method->update($request->all());

        return PaymentMethodResource::make($payment_method);
    }

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
    public function delete(PaymentMethod $payment_method)
    {
        $payment_method->delete();

        return response()->json(null, 204);
    }
}
