<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\SeoRequestContract;
use App\Rules\Boolean;
use App\Traits\BooleanRules;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;

class ProductSetUpdateRequest extends FormRequest implements SeoRequestContract
{
    use SeoRules, BooleanRules;

    protected array $booleanFields = [
        'slug_override',
        'public',
        'hide_on_index',
        'seo.no_index',
    ];

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
                'slug_override' => ['required', new Boolean()],
                'public' => ['required', new Boolean()],
                'hide_on_index' => ['required', new Boolean()],
                'parent_id' => ['present', 'nullable', 'uuid', 'exists:product_sets,id'],
                'children_ids' => ['present', 'array'],
                'children_ids.*' => ['uuid', 'exists:product_sets,id'],
                'description_html' => ['nullable', 'string'],
                'cover_id' => ['uuid', 'uuid', 'exists:media,id'],
                'attributes' => ['array'],
                'attributes.*' => ['uuid', 'exists:attributes,id'],
            ],
        );
    }
}
