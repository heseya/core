<?php

namespace App\Events;

use App\Http\Resources\CouponResource;
use App\Models\Discount;

class CouponEvent extends WebHookEvent
{
    protected Discount $coupon;

    public function __construct(Discount $coupon)
    {
        parent::__construct();
        $this->coupon = $coupon;
    }

    public function getDataContent(): array
    {
        return CouponResource::make($this->coupon)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->coupon);
    }
}
