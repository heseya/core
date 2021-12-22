<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderPublicResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'code' => $this->code,
            'status' => StatusResource::make($this->status),
            'paid' => $this->isPaid(),
            'payable' => $this->payable,
            'summary' => $this->summary,
            'shipping_method_id' => $this->shipping_method_id,
            'created_at' => $this->created_at,
        ];
    }
}
