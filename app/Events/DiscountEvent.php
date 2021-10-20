<?php

namespace App\Events;

use App\Models\Discount;

abstract class DiscountEvent extends WebHookEvent
{
    protected Discount $discount;

    public function __construct(Discount $discount)
    {
        parent::__construct();
        $this->discount = $discount;
    }

    public function getDataContent(): array
    {
        return $this->discount->toArray();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->discount);
    }
}
