<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CouponResource extends SaleResource
{
    public function base(Request $request): array
    {
        return parent::base($request) + [
            'code' => $this->resource->code,
        ] + $this->metadataResource('coupons.show_metadata_private');
    }
}
