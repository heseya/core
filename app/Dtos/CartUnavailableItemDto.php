<?php

namespace App\Dtos;

use Spatie\LaravelData\Data;

class CartUnavailableItemDto extends Data
{
    public function __construct(
        public string $cartitem_id,
        public float $quantity,
        public CartItemErrorDto $error,
    ) {}
}
