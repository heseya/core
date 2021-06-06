<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Contracts\DiscountServiceContract;

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

        $value += $order->shipping_price;

        return round($value < 0 ? 0 : $value, 2);
    }
}
