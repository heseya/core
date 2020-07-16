<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderPublicResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function base(Request $request): array
    {
        return [
            'code' => $this->code,
            'status' => StatusResource::make($this->status),
            'payed' => $this->isPayed(),
            'shipping_method_id' => $this->shipping_method_id,
            'created_at' => $this->created_at,
        ];
    }
}
