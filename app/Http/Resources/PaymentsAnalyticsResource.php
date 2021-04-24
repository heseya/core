<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentsAnalyticsResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'amount' => $this['amount'],
            'count' => $this['count'],
        ];
    }
}
