<?php

namespace App\Http\Requests;

use App\Rules\Price;
use App\Rules\PricesEveryCurrency;
use App\Rules\SchemaZeroOptionExistsForEachCurrency;
use App\Rules\Translations;
use App\Traits\MetadataRules;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SchemaStoreRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'translations' => ['required', new Translations(['name', 'description'])],
                'translations.*.name' => ['string', 'max:255'],
                'translations.*.description' => ['nullable', 'string', 'max:255'],

                'published' => ['required', 'array', 'min:1'],
                'published.*' => ['uuid', 'exists:languages,id'],

                'hidden' => ['nullable', 'boolean', 'declined_if:required,yes,on,1,true'],

                'required' => ['nullable', 'boolean'],

                'default' => ['nullable', 'required_if:required,true', 'in_array:options.*.translations.*'],

                'options' => ['required', 'array', Rule::when($this->required, [new SchemaZeroOptionExistsForEachCurrency()])],

                'options.*.translations' => ['required', new Translations(['name'])],
                'options.*.translations.*.name' => ['string', 'max:255'],

                'options.*.prices' => ['sometimes', 'required', new PricesEveryCurrency()],
                'options.*.prices.*' => ['sometimes', 'required', new Price(['value'], min: BigDecimal::zero())],

                'options.*.metadata' => ['array'],
                'options.*.metadata_private' => ['array'],

                'used_schemas' => ['nullable', 'array'],
                'used_schemas.*' => ['uuid', 'exists:schemas,id'],

                'options.*.items' => ['nullable', 'array'],
                'options.*.items.*' => ['uuid', 'exists:items,id'],

                'product_id' => ['uuid', 'exists:products,id', 'nullable'],
            ],
        );
    }
}
