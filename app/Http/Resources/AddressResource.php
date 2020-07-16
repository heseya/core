<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AddressResource extends Resource
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
