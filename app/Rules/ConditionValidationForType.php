<?php

namespace App\Rules;

use App\Enums\ConditionType;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class ConditionValidationForType implements DataAwareRule, ValidationRule, ValidatorAwareRule
{
    protected array $data;
    protected Validator $validator;

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $subValidator = FacadesValidator::make(
            $value,
            [
                'type' => [
                    'required',
                    new Enum(ConditionType::class),
                ],
                ...match ($value['type']) {
                    ConditionType::CART_LENGTH->value => [
                        'min_value' => (empty($value['max_value']) ? ['required'] : []) + ['integer'],
                        'max_value' => (empty($value['min_value']) ? ['required'] : ['gte:' . $value['min_value']]) + ['integer'],
                    ],
                    ConditionType::COUPONS_COUNT->value => [
                        'min_value' => (empty($value['max_value']) ? ['required'] : []) + ['integer'],
                        'max_value' => (empty($value['min_value']) ? ['required'] : ['gte:' . $value['min_value']]) + ['integer'],
                    ],
                    ConditionType::DATE_BETWEEN->value => [
                        'is_in_range' => ['required', 'boolean'],
                        'start_at' => (empty($value['end_at']) ? ['required'] : []) + ['date'],
                        'end_at' => (empty($value['start_at']) ? ['required'] : ['after_or_equal:' . $value['start_at']]) + ['date'],
                    ],
                    ConditionType::MAX_USES->value => [
                        'max_uses' => ['required', 'numeric'],
                    ],
                    ConditionType::MAX_USES_PER_USER->value => [
                        'max_uses' => ['required', 'numeric'],
                    ],
                    ConditionType::ORDER_VALUE->value => [
                        'is_in_range' => ['required', 'boolean'],
                        'include_taxes' => ['required', 'boolean'],
                        'min_values' => (empty($value['max_values']) ? ['required'] : ['nullable']) + ['array', new PricesEveryCurrency()],
                        'min_values.*' => [new Price(['value'], min: BigDecimal::zero())],
                        'max_values' => (empty($value['min_values']) ? ['required'] : ['nullable']) + ['array', new ConditionOrderValueMaxValuesGreaterThanMinValues($value), new PricesEveryCurrency()],
                        'max_values.*' => [new Price(['value'], min: BigDecimal::zero())],
                    ],
                    ConditionType::PRODUCT_IN->value => [
                        'is_allow_list' => ['required', 'boolean'],
                        'products' => ['required', 'array'],
                        'products.*' => ['uuid', 'exists:products,id'],
                    ],
                    ConditionType::PRODUCT_IN_SET->value => [
                        'is_allow_list' => ['required', 'boolean'],
                        'product_sets' => ['required', 'array'],
                        'product_sets.*' => ['uuid', 'exists:product_sets,id'],
                    ],
                    ConditionType::TIME_BETWEEN->value => [
                        'is_in_range' => ['required', 'boolean'],
                        'start_at' => (empty($value['end_at']) ? ['required'] : []) + ['date_format:H:i:s'],
                        'end_at' => (empty($value['start_at']) ? ['required'] : []) + ['date_format:H:i:s'],
                    ],
                    ConditionType::USER_IN->value => [
                        'is_allow_list' => ['required', 'boolean'],
                        'users' => ['required', 'array'],
                        'users.*' => ['uuid', 'exists:users,id'],
                    ],
                    ConditionType::USER_IN_ROLE->value => [
                        'is_allow_list' => ['required', 'boolean'],
                        'roles' => ['required', 'array'],
                        'roles.*' => ['uuid', 'exists:roles,id'],
                    ],
                    ConditionType::WEEKDAY_IN->value => [
                        'weekday' => ['required', 'array', 'min:7', 'max:7'],
                        'weekday.*' => ['required', 'boolean'],
                    ],
                    default => [],
                },
            ],
        );

        if ($subValidator->fails()) {
            foreach ($subValidator->errors()->messages() as $subAttribute => $messages) {
                foreach ($messages as $message) {
                    $this->validator->getMessageBag()->add($attribute, $message);
                }
            }
            $fail(':attribute has invalid or missing condition configuration');
        }
    }
}
