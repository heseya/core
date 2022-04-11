<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends ProductCreateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        // TODO: should be uncommented in future
//        $rules['metadata'] = ['prohibited'];
//        $rules['metadata_private'] = ['prohibited'];
        $rules['name'] = ['string', 'max:255'];
        $rules['price'] = ['numeric', 'min:0'];
        $rules['public'] = [new Boolean()];
        $rules['slug'] = [
            'string',
            'max:255',
            'alpha_dash',
            Rule::unique('products')->ignore($this->route('product')->slug, 'slug'),
        ];

        return $rules;
    }
}
