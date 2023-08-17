<?php

namespace App\Http\Requests;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Rules\Translations;
use App\Traits\MetadataRules;
use App\Traits\SeoRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
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
            'value' => ['required', 'numeric', 'gte:0'],
            'type' => ['required', new Enum(DiscountType::class)],
            'priority' => ['required', 'integer'],
            'target_type' => ['required', new Enum(DiscountTargetType::class)],
            'target_is_allow_list' => ['required', 'boolean'],
            'active' => ['boolean'],

            'condition_groups' => ['array'],
            'condition_groups.*.conditions' => ['required', 'array'],

            'condition_groups.*.conditions.*.type' => [
                'required',
                new Enum(ConditionType::class),
            ],

            'condition_groups.*.conditions.*.roles.*' => ['required', 'uuid', 'exists:roles,id'],
            'condition_groups.*.conditions.*.users.*' => ['required', 'uuid', 'exists:users,id'],
            'condition_groups.*.conditions.*.products.*' => ['required', 'uuid', 'exists:products,id'],
            'condition_groups.*.conditions.*.product_sets.*' => ['required', 'uuid', 'exists:product_sets,id'],
            'condition_groups.*.conditions.*.weekday.*' => ['required', 'boolean'],

            'target_products' => ['array'],
            'target_products.*' => ['uuid', 'exists:products,id'],

            'target_sets' => ['array'],
            'target_sets.*' => ['uuid', 'exists:product_sets,id'],

            'target_shipping_methods' => ['array'],
            'target_shipping_methods.*' => ['uuid', 'exists:shipping_methods,id'],
        ] + $this->seoRules() + $this->metadataRules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('value', ['max:100'], fn ($input, $item) => DiscountType::tryFrom($input->type) === DiscountType::PERCENTAGE);

        $validator->sometimes(
            'condition_groups.*.conditions.*.max_uses',
            ['required', 'numeric'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::MAX_USES,
                        ConditionType::MAX_USES_PER_USER,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.is_allow_list',
            ['required', 'boolean'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::PRODUCT_IN,
                        ConditionType::PRODUCT_IN_SET,
                        ConditionType::USER_IN,
                        ConditionType::USER_IN_ROLE,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.is_in_range',
            ['required', 'boolean'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::ORDER_VALUE,
                        ConditionType::DATE_BETWEEN,
                        ConditionType::TIME_BETWEEN,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.include_taxes',
            ['required', 'boolean'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::ORDER_VALUE,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.min_value',
            ['required_without:condition_groups.*.conditions.*.max_value', 'numeric'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::ORDER_VALUE,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.max_value',
            [
                'required_without:condition_groups.*.conditions.*.min_value',
                'numeric',
                'gte:condition_groups.*.conditions.*.min_value',
            ],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::ORDER_VALUE,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.start_at',
            ['required_without:condition_groups.*.conditions.*.end_at', 'date_format:H:i:s'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::TIME_BETWEEN,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.end_at',
            ['required_without:condition_groups.*.conditions.*.start_at', 'date_format:H:i:s'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::TIME_BETWEEN,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.start_at',
            ['required_without:condition_groups.*.conditions.*.end_at', 'date'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::DATE_BETWEEN,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.end_at',
            [
                'required_without:condition_groups.*.conditions.*.start_at',
                'date',
                'after_or_equal:condition_groups.*.conditions.*.start_at',
            ],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::DATE_BETWEEN,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.weekday',
            ['required', 'array', 'min:7', 'max:7'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::WEEKDAY_IN,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.roles',
            ['array'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::USER_IN_ROLE,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.users',
            ['array'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::USER_IN,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.products',
            ['array'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::PRODUCT_IN,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.product_sets',
            ['array'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::PRODUCT_IN_SET,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.min_value',
            ['required_without:condition_groups.*.conditions.*.max_value', 'integer'],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::CART_LENGTH,
                        ConditionType::COUPONS_COUNT,
                    ]
                );
            }
        );

        $validator->sometimes(
            'condition_groups.*.conditions.*.max_value',
            [
                'required_without:condition_groups.*.conditions.*.min_value',
                'integer',
                'gte:condition_groups.*.conditions.*.min_value',
            ],
            function ($input, $item) {
                return in_array(
                    ConditionType::tryFrom($item->type),
                    [
                        ConditionType::CART_LENGTH,
                        ConditionType::COUPONS_COUNT,
                    ]
                );
            }
        );
    }
}
