<?php

namespace App\Http\Requests;

class ProductSetStoreRequest extends SeoMetadataRulesRequest
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
            'public' => ['boolean'],
            'hide_on_index' => ['boolean'],
            'parent_id' => ['uuid', 'nullable', 'exists:product_sets,id'],
            'children_ids' => ['array'],
            'children_ids.*' => ['uuid', 'exists:product_sets,id'],
        ]);
    }
}
