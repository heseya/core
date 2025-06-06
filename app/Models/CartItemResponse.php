<?php

namespace App\Models;

use Brick\Money\Money;

class CartItemResponse
{
    public function __construct(
        public string $cartitem_id,
        public Money $price,
        public Money $price_discounted,
        public float $quantity,
        public string $product_id,
        public string|null $related_product_id = null,
    ) {}
}
