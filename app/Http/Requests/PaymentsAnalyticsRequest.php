<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentsAnalyticsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date', 'required_with:to'],
            'to' => ['nullable', 'date'],
        ];
    }
}
