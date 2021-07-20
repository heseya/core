<?php

namespace App\Services\Contracts;

use App\Models\Discount;

interface DiscountServiceContract
{
    public function calc(float $value, Discount $discount): float;
}
