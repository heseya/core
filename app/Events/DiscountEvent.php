<?php

namespace App\Events;

use App\Http\Resources\DiscountResource;
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
        return DiscountResource::make($this->discount)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->discount);
    }
}
