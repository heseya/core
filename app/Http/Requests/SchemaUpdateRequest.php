<?php

namespace App\Http\Requests;

use App\Enums\SchemaType;
use App\Rules\EnumKey;
use App\Rules\Price;
use App\Rules\PricesEveryCurrency;
use App\Rules\Translations;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;

class SchemaUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'translations' => ['sometimes', new Translations(['name', 'description'])],
            'translations.*.name' => ['sometimes', 'string', 'max:255'],
            'translations.*.description' => ['sometimes', 'nullable', 'string', 'max:255'],

            'published' => ['sometimes', 'array', 'min:1'],
            'published.*' => ['sometimes', 'uuid', 'exists:languages,id'],

            'type' => ['sometimes', 'string', new EnumKey(SchemaType::class)],

            'hidden' => ['nullable', 'boolean', 'declined_if:required,yes,on,1,true'],
            'required' => ['nullable', 'boolean'],

            'options' => ['nullable', 'array'],
            'options.*.translations' => ['sometimes', new Translations(['name'])],
            'options.*.translations.*.name' => ['sometimes', 'string', 'max:255'],

            'options.*.prices' => ['sometimes', 'required', new PricesEveryCurrency()],
            'options.*.prices.*' => ['sometimes', 'required', new Price(['value'], min: BigDecimal::zero())],

            'options.*.metadata' => ['array'],
            'options.*.metadata_private' => ['array'],

            'options.*.default' => ['sometimes', 'boolean'],

            'options.*.items' => ['nullable', 'array'],
            'options.*.items.*' => ['uuid', 'exists:items,id'],

            'used_schemas' => ['nullable', 'array'],
            'used_schemas.*' => ['uuid', 'exists:schemas,id'],

            'product_id' => ['uuid', 'exists:products,id', 'nullable'],
        ];
    }
}
