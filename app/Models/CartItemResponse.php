<?php

namespace App\Models;

class CartItemResponse
{
    public function __construct(
        public string $cartitem_id,
        public float $price,
        public float $price_discounted,
    ) {
    }
}
