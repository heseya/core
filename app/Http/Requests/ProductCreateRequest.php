<?php

namespace App\Http\Requests;

use App\Rules\CommaSeparatedUuids;

class ProductCreateRequest extends SeoMetadataRulesRequest
{
    public function rules(): array
    {
        return $this->rulesWithSeo([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products', 'alpha_dash'],
            'price' => ['required', 'numeric', 'min:0'],
            'description_html' => ['nullable', 'string'],
            'description_short' => ['nullable', 'string', 'between:30,5000'],
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

            'attributes' => ['nullable', 'array'],
            'attributes.*' => [new CommaSeparatedUuids(['attributes,id', 'attribute_options,id'])],
        ]);
    }
}
