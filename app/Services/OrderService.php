<?php

namespace App\Services;

use App\Exceptions\OrderException;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Services\Contracts\DiscountServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Exception;

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
        if (!$order->id) {
            throw new OrderException('Order model does not exist !', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();

        try {
            $deliveryAddress = Address::updateOrCreate(
                [
                    'id' => $order->delivery_address_id,
                ],
                $request->delivery_address
            );

            if ($request->invoice_address) {
                $invoiceAddress = Address::updateOrCreate(
                    [
                        'id' => $order->invoice_address_id,
                    ],
                    $request->invoice_address
                );
            }

            $order->update([
                 'email' => $request->input('email'),
                 'comment' => $request->input('comment'),
                 'delivery_address_id' => $deliveryAddress->getKey(),
                 'invoice_address_id' => $request->invoice_address ? $invoiceAddress->getKey() : null,
            ]);

            DB::commit();

            return OrderResource::make($order)->response();
        } catch (Exception $error) {
            DB::rollBack();

            throw new OrderException('Error editing the order for id: ' . $order->id);
        }
    }
}
