<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatusCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'color' => ['required', 'string', 'size:6'],
            'cancel' => ['boolean'],
            'description' => ['string', 'max:255', 'nullable'],
            'hidden' => ['boolean'],
            'no_notifications' => ['boolean'],
        ];
    }
}
