<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatusOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'statuses' => ['required', 'array'],
            'statuses.*' => ['uuid', 'exists:statuses,id'],
        ];
    }
}