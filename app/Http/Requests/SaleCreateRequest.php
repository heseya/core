<?php

namespace App\Http\Requests;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Rules\ConditionValidationForType;
use App\Rules\Price;
use App\Rules\PricesEveryCurrency;
use App\Rules\Translations;
use App\Traits\MetadataRules;
use App\Traits\SeoRules;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class SaleCreateRequest extends FormRequest
{
    use MetadataRules;
    use SeoRules;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'translations' => ['required', new Translations(['name', 'description_html', 'description'])],
            'slug' => ['nullable', 'string', 'max:128', 'alpha_dash'],

            'percentage' => ['nullable', 'required_without:amounts', 'prohibits:amounts', 'numeric', 'string', 'gte:0'],
            'amounts' => ['nullable', 'required_without:percentage', 'prohibits:percentage', new PricesEveryCurrency()],
            'amounts.*' => [new Price(['value'], min: BigDecimal::zero())],

            'priority' => ['required', 'integer'],
            'target_type' => ['required', new Enum(DiscountTargetType::class)],
            'target_is_allow_list' => ['required', 'boolean'],
            'active' => ['boolean'],

            'type' => [new Enum(DiscountType::class)],
            'value' => [Rule::when(DiscountType::tryFrom($this->input('type')) === DiscountType::PERCENTAGE, ['max:100'])],

            'condition_groups' => ['array'],
            'condition_groups.*.conditions' => ['required', 'array'],
            'condition_groups.*.conditions.*' => [
                'array',
                new ConditionValidationForType(),
            ],

            'target_products' => ['array'],
            'target_products.*' => ['uuid', 'exists:products,id'],

            'target_sets' => ['array'],
            'target_sets.*' => ['uuid', 'exists:product_sets,id'],

            'target_shipping_methods' => ['array'],
            'target_shipping_methods.*' => ['uuid', 'exists:shipping_methods,id'],
        ] + $this->seoRules() + $this->metadataRules();
    }
}
