<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderSyncRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'status_id' => ['required', 'uuid', 'exists:statuses,id'],
            'shipping_method_id' => ['required', 'uuid', 'exists:shipping_methods,id'],
        ];
    }
}
