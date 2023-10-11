<?php

namespace App\Http\Requests;

use App\Rules\AttributeSearch;
use App\Rules\CanShowPrivateMetadata;
use App\Rules\Price;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
    public function rules(): array
    {
        $setsExist = Rule::exists('product_sets', 'slug');

        if (Gate::denies('product_sets.show_hidden')) {
            $setsExist = $setsExist
                ->where('public', true)
                ->where('public_parent', true);
        }

        return [
            'search' => ['sometimes', 'string', 'max:255'],

            'ids' => ['array'],
            'ids.*' => ['uuid'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'public' => ['boolean'],
            'sort' => ['nullable', 'string', 'max:255'],
            'available' => ['nullable'],
            'has_cover' => ['boolean'],
            'has_items' => ['boolean'],
            'has_schemas' => ['boolean'],
            'shipping_digital' => ['boolean'],

            'sets' => ['nullable', 'array'],
            'sets.*' => ['string', $setsExist],
            'sets_not' => ['nullable', 'array'],
            'sets_not.*' => ['string', $setsExist],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'uuid'],
            'tags_not' => ['nullable', 'array'],
            'tags_not.*' => ['string', 'uuid'],

            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['filled'],
            'metadata_private' => ['nullable', 'array', new CanShowPrivateMetadata()],
            'metadata_private.*' => ['filled'],

            'price' => ['nullable', new Price(['min', 'max'], min: BigDecimal::zero(), nullable: true)],

            'attribute' => ['nullable', 'array'],
            'attribute.*' => [new AttributeSearch()],
            'attribute_not' => ['nullable', 'array'],
            'attribute_not.*' => [new AttributeSearch()],

            'full' => ['boolean'],
        ];
    }
}
