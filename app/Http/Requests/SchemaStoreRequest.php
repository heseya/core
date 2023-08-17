<?php

namespace App\Http\Requests;

use App\Enums\SchemaType;
use App\Rules\EnumKey;
use App\Rules\Price;
use App\Rules\PricesEveryCurrency;
use App\Rules\Translations;
use App\Traits\MetadataRules;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;

final class SchemaStoreRequest extends FormRequest
{
    use MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'translations' => [
                    'required',
                    new Translations(['name', 'description']),
                ],
                'translations.*.name' => ['string', 'max:255'],
                'translations.*.description' => ['nullable', 'string', 'max:255'],

                'published' => ['required', 'array', 'min:1'],
                'published.*' => ['uuid', 'exists:languages,id'],

                'type' => ['required', 'string', new EnumKey(SchemaType::class)],

                'prices' => ['required', new PricesEveryCurrency()],
                'prices.*' => [new Price(['value'], min: BigDecimal::zero())],

                'hidden' => ['nullable', 'boolean'],
                'required' => ['nullable', 'boolean'],
                'min' => ['nullable', 'numeric', 'min:-100000', 'max:100000'],
                'max' => ['nullable', 'numeric', 'min:-100000', 'max:100000'],
                'step' => ['nullable', 'numeric', 'min:0', 'max:100000'],
                'default' => ['nullable'],
                'pattern' => ['nullable', 'string', 'max:255'],
                'validation' => ['nullable', 'string', 'max:255'],

                'options' => ['nullable', 'array'],
                'options.*.translations' => [
                    'required',
                    new Translations(['name']),
                ],
                'options.*.translations.*.name' => ['string', 'max:255'],

                'options.*.prices' => ['sometimes', 'required', new PricesEveryCurrency()],
                'options.*.prices.*' => ['sometimes', 'required', new Price(['value'], min: BigDecimal::zero())],

                'options.*.disabled' => ['sometimes', 'required', 'boolean'],
                'options.*.metadata' => ['array'],
                'options.*.metadata_private' => ['array'],

                'used_schemas' => ['nullable', 'array'],
                'used_schemas.*' => ['uuid', 'exists:schemas,id'],

                'options.*.items' => ['nullable', 'array'],
                'options.*.items.*' => ['uuid', 'exists:items,id'],
            ]
        );
    }
}
