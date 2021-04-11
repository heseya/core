<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentMethodResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'alias' => $this->alias,
            'public' => $this->public,
        ];
    }
}
