<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\OrderPublicResourceSwagger;
use Illuminate\Http\Request;

class OrderPublicResource extends Resource implements OrderPublicResourceSwagger
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'code' => $this->code,
            'status' => StatusResource::make($this->status),
            'payed' => $this->isPayed(),
            'payable' => $this->payable,
            'summary' => $this->summary,
            'shipping_method_id' => $this->shipping_method_id,
            'created_at' => $this->created_at,
        ];
    }
}
