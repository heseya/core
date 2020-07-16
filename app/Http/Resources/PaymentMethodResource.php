<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentMethodResource extends Resource
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
            'alias' => $this->alias,
            'public' => $this->public,
        ];
    }
}
