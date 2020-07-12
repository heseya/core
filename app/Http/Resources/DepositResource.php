<?php

namespace App\Http\Resources;

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
