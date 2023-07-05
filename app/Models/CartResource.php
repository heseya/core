<?php

namespace App\Models;

use Brick\Money\Money;
use Illuminate\Support\Collection;

class CartResource
{
    public function __construct(
        public Collection $items,
        public Collection $coupons,
        public Collection $sales,
        public Money $cart_total_initial,
        public Money $cart_total,
        public Money $shipping_price_initial,
        public Money $shipping_price,
        public ?Money $shipping_time = null,
        public ?string $shipping_date = null,
        public Money $summary,
    ) {
    }
}
