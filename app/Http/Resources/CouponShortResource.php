<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CouponShortResource extends SalesShortResource
{
    public function base(Request $request): array
    {
        return parent::base($request) + ['code' => $this->resource->code];
    }
}
