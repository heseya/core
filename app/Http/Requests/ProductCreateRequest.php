<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\MetadataRequestContract;
use App\Http\Requests\Contracts\SeoRequestContract;
use App\Rules\AttributeOptionExist;
use App\Rules\Money;
use App\Rules\ProductAttributeOptions;
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
            $this->seoRules(),
            $this->metadataRules(),
            [
                'id' => ['uuid'],

                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'unique:products', 'alpha_dash'],

                'prices_base' => ['required', 'array', 'size:1'],
                'prices_base.*' => [new Money(min: BigDecimal::zero())],

                'public' => ['required', 'boolean'],
                'shipping_digital' => ['required', 'boolean'],

                'description_html' => ['nullable', 'string'],
                'description_short' => ['nullable', 'string', 'max:5000'],

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
