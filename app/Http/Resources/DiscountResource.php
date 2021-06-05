<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->getKey(),
            'code' => $this->code,
            'description' => $this->description,
            'discount' => $this->discount,
            'type' => $this->type,
            'uses' => $this->uses,
            'max_uses' => $this->max_uses,
        ];
    }
}
