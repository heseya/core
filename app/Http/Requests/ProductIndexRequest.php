<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Rules\CanShowPrivateMetadata;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'public',
        'full',
    ];

    public function rules(): array
    {
        $setsExist = Rule::exists('product_sets', 'slug');

        if (Gate::denies('product_sets.show_hidden')) {
            $setsExist = $setsExist
                ->where('public', true)
                ->where('public_parent', true);
        }

        return [
            'search' => ['nullable', 'string', 'max:255'],

            'ids' => ['string'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'public' => [new Boolean()],
            'sort' => ['nullable', 'string', 'max:255'],
            'available' => ['nullable'],

            'sets' => ['nullable', 'array'],
            'sets.*' => ['string', $setsExist],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'uuid'],

            'metadata' => ['nullable', 'array'],
            'metadata_private' => ['nullable', 'array', new CanShowPrivateMetadata()],

            'price' => ['nullable', 'array'],
            'price.min' => ['nullable', 'numeric', 'min:0'],
            'price.max' => ['nullable', 'numeric'],

            'full' => [new Boolean()],
        ];
    }
}
