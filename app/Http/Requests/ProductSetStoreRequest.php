<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;

class ProductSetStoreRequest extends FormRequest implements SeoRequestContract
{
    use SeoRules;

    public function rules(): array
    {
        return array_merge(
            $this->seoRules(),
            [
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
                'description_html' => ['nullable', 'string'],
                'cover_id' => ['uuid', 'uuid', 'exists:media,id'],
                'attributes' => ['array'],
                'attributes.*' => ['uuid', 'exists:attributes,id'],
            ],
        );
    }
}
