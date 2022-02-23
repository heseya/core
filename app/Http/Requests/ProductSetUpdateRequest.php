<?php

namespace App\Http\Requests;

class ProductSetUpdateRequest extends SeoMetadataRulesRequest
{
    public function rules(): array
    {
        return $this->rulesWithSeo([
            'name' => ['required', 'string', 'max:255'],
            'slug_suffix' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
            ],
            'slug_override' => ['required', 'boolean'],
            'public' => ['required', 'boolean'],
            'hide_on_index' => ['required', 'boolean'],
            'parent_id' => ['present', 'nullable', 'uuid', 'exists:product_sets,id'],
            'children_ids' => ['present', 'array'],
            'children_ids.*' => ['uuid', 'exists:product_sets,id'],
            'description_html' => ['nullable', 'string'],
            'cover_id' => ['uuid', 'uuid', 'exists:media,id'],
        ]);
    }
}
