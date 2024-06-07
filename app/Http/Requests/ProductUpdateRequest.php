<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\PricesEveryCurrency;
use App\Rules\Translations;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends ProductCreateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        /** @var Product $product */
        $product = $this->route('product');

        // TODO: should be uncommented in future when admin panel remove metadata from payload
        // $rules['metadata'] = ['prohibited'];
        // $rules['metadata_private'] = ['prohibited'];
        $rules['name'] = ['string', 'max:255'];
        $rules['prices_base'] = [new PricesEveryCurrency()];
        $rules['public'] = ['boolean'];
        $rules['shipping_digital'] = ['boolean'];
        $rules['slug'] = [
            'string',
            'max:255',
            'alpha_dash',
            Rule::unique('products', 'slug')->whereNull('deleted_at')->ignoreModel($product),
        ];

        $rules['published'] = ['nullable', 'array', 'min:1'];
        $rules['translations'] = [
            'nullable',
            new Translations(['name', 'description_html', 'description_short']),
        ];
        $rules['banner'] = ['nullable', 'array'];

        return $rules;
    }
}
