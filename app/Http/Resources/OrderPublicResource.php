<?php

namespace App\Http\Resources;

class OrderPublicResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function base($request): array
    {
        return [
            'code' => $this->code,
            'payment_status' => $this->payment_status,
            'shop_status' => $this->shop_status,
            'delivery_status' => $this->delivery_status,
            'created_at' => $this->created_at,
        ];
    }
}
