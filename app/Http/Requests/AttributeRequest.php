<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttributeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['required'],
            'type' => ['required'],
            'searchable' => ['required'],
            'options' => ['required'],
        ];
    }
}
