<?php

namespace App\Http\Requests;

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
            'search' => ['nullable', 'string', 'max:255'],

            'ids' => ['string'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'public' => ['nullable', 'boolean'],
            'sets' => ['nullable', 'array'],
            'sets.*' => [
                'string',
                $setsExist,
            ],
            'sort' => ['nullable', 'string', 'max:255'],
            'available' => ['nullable'],
            'tags' => ['nullable', 'array'],
            'tags.*' => [
                'string',
                'uuid',
            ],

            'full' => ['nullable', 'boolean'],
        ];
    }
}
