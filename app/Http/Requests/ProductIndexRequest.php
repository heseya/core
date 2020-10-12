<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'brand' => 'string|max:255',
            'category' => 'string|max:255',
            'search' => 'string|max:255',
            'sort' => 'string|max:255',
        ];
    }
}
