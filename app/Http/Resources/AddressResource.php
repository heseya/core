<?php

namespace App\Http\Resources;

class AddressResource extends Resource
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
            'name' => $this->name,
            'address' => $this->address,
            'vat' => $this->vat,
            'zip' => $this->zip,
            'city' => $this->city,
            'country' => $this->country,
            'phone' => $this->phone,
        ];
    }
}
