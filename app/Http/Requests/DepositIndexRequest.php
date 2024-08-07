<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'ids' => ['array'],
            'ids.*' => ['uuid'],
        ];
    }
}
