<?php

namespace App\Services;

use App\Exceptions\OrderException;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderNote;
use App\Services\Contracts\DiscountServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected DiscountServiceContract $discountService;

    public function __construct(DiscountServiceContract $discountService)
    {
        $this->discountService = $discountService;
    }

    public function calcSummary(Order $order): float
    {
        $value = 0;
        foreach ($order->products as $item) {
            $value += $item->price * $item->quantity;
        }

        $cartValue = $value;
        foreach ($order->discounts as $discount) {
            $value -= $this->discountService->calc($cartValue, $discount);
        }

        $value = ($value < 0 ? 0 : $value) + $order->shipping_price;

        return round($value, 2);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $model = Order::find($order->id);
        if (!$model) {
            throw new OrderException('Order model does not exist !', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            OrderNote::updateOrCreate(
                [
                   'order_id' => $order->id,
                   'user_id' => $request->user()->id ?? Auth::user()->id,
                ],
                [
                    'message' => $request->note,
                ]
            );

            $deliveryAddress = Address::updateOrCreate(
                [
                    'id' => $order->delivery_address_id,
                ],
                $request->delivery_address
            );

            $invoiceAddress = Address::updateOrCreate(
                [
                    'id' => $order->invoice_address_id,
                ],
                $request->invoice_address
            );

            $order->update([
                 'email' => $request->email,
                 'delivery_address_id' => $deliveryAddress->id ?? null,
                 'invoice_address_id' => $invoiceAddress->id ?? null,
            ]);

            DB::commit();

            return OrderResource::make($order)->response();
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error('[' . __METHOD__ . '] ' . $error->getMessage());
            throw new OrderException('Error editing the order for id: ' . $order->id);
        }
    }
}
