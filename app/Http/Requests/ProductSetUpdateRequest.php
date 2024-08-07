<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;

class ProductSetUpdateRequest extends FormRequest implements SeoRequestContract
{
    use SeoRules;

    public function rules(): array
    {
        return array_merge(
            $this->seoRules(),
            [
                'name' => ['string', 'max:255'],
                'slug_suffix' => [
                    'string',
                    'max:255',
                    'alpha_dash',
                ],
                'slug_override' => ['boolean'],
                'public' => ['boolean'],
                'parent_id' => ['present', 'nullable', 'uuid', 'exists:product_sets,id'],
                'children_ids' => ['present', 'array'],
                'children_ids.*' => ['uuid', 'exists:product_sets,id'],
                'description_html' => ['nullable', 'string'],
                'cover_id' => ['uuid', 'uuid', 'exists:media,id'],
                'attributes' => ['array'],
                'attributes.*' => ['uuid', 'exists:attributes,id'],
                'tree' => ['boolean'],
            ],
        );
    }
}
