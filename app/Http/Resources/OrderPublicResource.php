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
            'status' => StatusResource::make($this->status),
            'is_payed' => $this->isPayed(),
            'created_at' => $this->created_at,
        ];
    }
}
