<?php

namespace App\Events;

use App\Models\Discount;

abstract class DiscountEvent extends WebHookEvent
{
    protected Discount $discount;

    public function __construct(Discount $discount)
    {
        $this->discount = $discount;
    }

    public function getData(): array
    {
        return $this->discount->toArray();
    }
}
