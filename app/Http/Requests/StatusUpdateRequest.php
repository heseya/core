<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatusUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:60'],
            'color' => ['string', 'size:6'],
            'cancel' => ['boolean'],
            'description' => ['string', 'max:255', 'nullable'],
            'hidden' => ['boolean'],
            'no_notifications' => ['boolean'],
        ];
    }
}
