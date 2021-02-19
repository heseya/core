<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products', 'alpha_dash'],
            'price' => ['required', 'numeric'],
            'brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'description_md' => ['nullable', 'string'],
            'public' => ['required', 'boolean'],

            'media' => ['nullable', 'array'],
            'media.*' => ['uuid', 'exists:media,id'],
        ];
    }
}
