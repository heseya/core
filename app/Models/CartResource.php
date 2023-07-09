<?php

namespace App\Models;

use Illuminate\Support\Collection;

class CartResource
{
    public function __construct(
        public Collection $items,
        public Collection $coupons,
        public Collection $sales,
        public float $cart_total_initial = 0,
        public float $cart_total = 0,
        public float $shipping_price_initial = 0,
        public float $shipping_price = 0,
        public ?float $shipping_time = null,
        public ?string $shipping_date = null,
        public float $summary = 0,
    ) {}
}
