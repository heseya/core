<?php

namespace App\Dtos;

use Spatie\LaravelData\Data;

class CartItemErrorDto extends Data
{
    public function __construct(
        public string $key,
        public string $message,
    ) {}
}
