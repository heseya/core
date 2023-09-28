<?php

namespace App\Http\Resources;

use App\Models\Price;
use Illuminate\Http\Request;

/**
 * @property Price $resource
 */
class PriceWithSalesChannelResource extends PriceResource
{
    public function base(Request $request): array
    {
        return array_merge(parent::base($request), [
            'sales_channel_id' => $this->resource->sales_channel_id,
        ]);
    }
}
