<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevenueAnalyticsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
