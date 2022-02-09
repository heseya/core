<?php

namespace App\Http\Requests;

use App\Rules\Translations;

class ProductCreateRequest extends SeoMetadataRulesRequest
{
    public function rules(): array
    {
        return $this->rulesWithSeo([
            'translations' => [
                'required',
                new Translations(['name', 'description_html', 'description_short']),
            ],
//            'name' => ['required', 'string', 'max:255'],
//            'description_html' => ['nullable', 'string'],
//            'description_short' => ['nullable', 'string', 'between:30,5000'],

            'published' => ['required', 'array', 'min:1'],
            'published.*' => ['uuid', 'exists:languages,id'],

            'slug' => ['required', 'string', 'max:255', 'unique:products', 'alpha_dash'],
            'price' => ['required', 'numeric', 'min:0'],
            'public' => ['required', 'boolean'],
            'quantity_step' => ['numeric'],

            'media' => ['nullable', 'array'],
            'media.*' => ['uuid', 'exists:media,id'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['uuid', 'exists:tags,id'],

            'schemas' => ['nullable', 'array'],
            'schemas.*' => ['uuid', 'exists:schemas,id'],

            'sets' => ['nullable', 'array'],
            'sets.*' => ['uuid', 'exists:product_sets,id'],
        ]);
    }
}
