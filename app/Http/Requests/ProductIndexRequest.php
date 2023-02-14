<?php

namespace App\Http\Requests;

use App\Rules\AttributeSearch;
use App\Rules\Boolean;
use App\Rules\CanShowPrivateMetadata;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
    public function rules(): array
    {
        $setsExist = Rule::exists('product_sets', 'slug');

        if (Gate::denies('product_sets.show_hidden')) {
            // Ignored for phpstan because laravel have bad types
            $setsExist = $setsExist
                ->where('public', true) // @phpstan-ignore-line
                ->where('public_parent', true); // @phpstan-ignore-line
        }

        return [
            'search' => ['nullable', 'string', 'max:255'],

            'ids' => ['array'],
            'ids.*' => ['uuid'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'public' => [new Boolean()],
            'sort' => ['nullable', 'string', 'max:255'],
            'available' => ['nullable'],
            'has_cover' => [new Boolean()],
            'has_items' => [new Boolean()],
            'has_schemas' => [new Boolean()],
            'shipping_digital' => [new Boolean()],

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

            'price' => ['nullable', 'array'],
            'price.min' => ['nullable', 'numeric', 'min:0'],
            'price.max' => ['nullable', 'numeric'],

            'attribute' => ['nullable', 'array'],
            'attribute.*' => [new AttributeSearch()],
            'attribute_not' => ['nullable', 'array'],
            'attribute_not.*' => [new AttributeSearch()],

            'full' => [new Boolean()],
        ];
    }
}
