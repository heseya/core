<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderUpdateRequest extends OrderCreateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['shipping_method_id'] = [
            'nullable', 'uuid', 'exists:shipping_methods,id'
        ];

        foreach ($rules as $key => $item) {
            if (preg_match('/^item(.+)/i', $key)) {
                unset($rules[$key]);
            }
        }

        return $rules;
    }
}
