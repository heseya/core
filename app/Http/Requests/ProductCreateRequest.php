<?php

namespace App\Http\Requests;

use App\Http\Requests\Contracts\MetadataRequestContract;
use App\Http\Requests\Contracts\SeoRequestContract;
use App\Rules\AttributeOptionExist;
use App\Rules\ProductAttributeOptions;
use App\Rules\UniqueIdInRequest;
use App\Traits\MetadataRules;
use App\Traits\SeoRules;
use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest implements SeoRequestContract, MetadataRequestContract
{
    use SeoRules, MetadataRules;

    public function rules(): array
    {
        return array_merge(
            $this->seoRules(),
            $this->metadataRules(),
            [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'unique:products', 'alpha_dash'],
                'price' => ['required', 'numeric', 'min:0'],
                'public' => ['required', 'boolean'],
                'shipping_digital' => ['required', 'boolean'],

                'description_html' => ['nullable', 'string'],
                'description_short' => ['nullable', 'string', 'between:30,5000'],

                'quantity_step' => ['numeric'],
                'order' => ['nullable', 'numeric'],
                'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'purchase_limit_per_user' => ['nullable', 'numeric', 'min:0'],

                'media' => ['nullable', 'array'],
                'media.*' => ['uuid', 'exists:media,id'],

                'tags' => ['nullable', 'array'],
                'tags.*' => ['uuid', 'exists:tags,id'],

                'attributes' => ['nullable', 'array'],
                'attributes.*' => ['bail', 'array', new ProductAttributeOptions()],
                'attributes.*.*' => ['uuid', new AttributeOptionExist()],

                'items' => ['nullable', 'array', new UniqueIdInRequest()],
                'items.*.id' => ['uuid', 'exists:items,id'],
                'items.*.required_quantity' => ['numeric', 'gte:0.0001'],

                'schemas' => ['nullable', 'array'],
                'schemas.*' => ['uuid', 'exists:schemas,id'],

                'sets' => ['nullable', 'array'],
                'sets.*' => ['uuid', 'exists:product_sets,id'],
                'google_product_category' => [
                    'nullable',
                    'integer',
                ],
            ],
        );
    }
}
