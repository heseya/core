<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\MetadataRequestContract;
use App\Http\Requests\Contracts\SeoRequestContract;
use App\Rules\AttributeOptionExist;
use App\Rules\Price;
use App\Rules\PricesEveryCurrency;
use App\Rules\ProductAttributeOptions;
use App\Rules\Translations;
use App\Rules\UniqueIdInRequest;
use App\Traits\MetadataRules;
use App\Traits\SeoRules;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest implements MetadataRequestContract, SeoRequestContract
{
    use MetadataRules;
    use SeoRules;

    public function rules(): array
    {
        return array_merge(
            $this->metadataRules(),
            [
                'id' => ['uuid'],
                'translations' => [
                    'required',
                    new Translations(['name', 'description_html', 'description_short']),
                ],
                'translations.*.name' => ['required', 'string', 'max:255'],
                'translations.*.description_html' => ['nullable', 'string'],
                'translations.*.description_short' => ['nullable', 'string', 'max:5000'],

                'published' => ['required', 'array', 'min:1'],
                'published.*' => ['uuid', 'exists:languages,id'],

                'slug' => ['required', 'string', 'max:255', 'unique:products', 'alpha_dash'],

                'prices_base' => ['required', new PricesEveryCurrency()],
                'prices_base.*' => [new Price(['value'], min: BigDecimal::zero())],

                'public' => ['required', 'boolean'],
                'shipping_digital' => ['required', 'boolean'],

                'quantity_step' => ['numeric'],
                'order' => ['numeric'],
                'vat_rate' => ['numeric', 'min:0', 'max:100'],
                'purchase_limit_per_user' => ['nullable', 'numeric', 'min:0'],

                'media' => ['array'],
                'media.*' => ['uuid', 'exists:media,id'],

                'tags' => ['array'],
                'tags.*' => ['uuid', 'exists:tags,id'],

                'attributes' => ['array'],
                'attributes.*' => ['bail', 'array', new ProductAttributeOptions()],
                'attributes.*.*' => ['uuid', new AttributeOptionExist()],

                'items' => ['array', new UniqueIdInRequest()],
                'items.*' => ['array'],
                'items.*.id' => ['uuid', 'exists:items,id'],
                'items.*.required_quantity' => ['numeric', 'gte:0.0001'],

                'schemas' => ['array'],
                'schemas.*' => ['uuid', 'exists:schemas,id'],

                'sets' => ['array'],
                'sets.*' => ['uuid', 'exists:product_sets,id'],
                'google_product_category' => [
                    'nullable',
                    'integer',
                ],

                'related_sets' => ['array'],
                'related_sets.*' => ['uuid', 'exists:product_sets,id'],
            ],
        );
    }
}
