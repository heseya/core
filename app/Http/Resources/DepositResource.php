<?php

namespace App\Http\Resources;

use App\Http\Resources\MediaResource;
use App\Http\Resources\CategoryResource;

class DepositResource extends Resource
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
            'id' => $this->id,
            'quantity' => $this->quantity,
            'item_id' => $this->item_id,
        ];
    }
}
