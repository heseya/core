<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use Illuminate\Foundation\Http\FormRequest;

class StatusUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:60'],
            'color' => ['string', 'size:6'],
            'cancel' => [new Boolean()],
            'description' => ['string', 'max:255', 'nullable'],
            'hidden' => [new Boolean()],
            'no_notifications' => [new Boolean()],
        ];
    }
}
