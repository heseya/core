<?php

namespace App\Rules;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AttributeSearch implements Rule
{
    private string $message;
    private string $attributeName = '';
    private ?Attribute $attribute;

    public function message(): string
    {
        return $this->message;
    }

    public function passes($attribute, $value): bool
    {
        $this->attributeName = Str::between($attribute, '.', '.');
        $this->message = "The attribute `{$this->attributeName}` isn't supported.";
        $this->attribute = Attribute::query()->where('slug', $this->attributeName)->first();

        if ($this->attribute === null) {
            $this->message = "The attribute `{$this->attributeName}` doesn't exists.";

            return false;
        }

        return match ($this->attribute->type) {
            AttributeType::NUMBER => $this->validateMinMax($value) && $this->validateNumber($value),
            AttributeType::DATE => $this->validateMinMax($value) && $this->validateDate($value),
            AttributeType::SINGLE_OPTION, AttributeType::MULTI_CHOICE_OPTION => $this->validateOptions($value),
        };
    }

    private function validateMinMax(mixed $value): bool
    {
        if (is_array($value) && Arr::hasAny($value, ['min', 'max'])) {
            return true;
        }

        $this->message = "Min or max value for attribute `{$this->attributeName}` must be provided.";

        return false;
    }

    private function validateNumber(mixed $value): bool
    {
        if (Arr::has($value, 'min') && !is_numeric($value['min'])) {
            $this->message = "Min value for attribute `{$this->attributeName}` must be a number.";

            return false;
        }

        if (Arr::has($value, 'max') && !is_numeric($value['max'])) {
            $this->message = "Max value for attribute `{$this->attributeName}` must be a number.";

            return false;
        }

        return true;
    }

    // TODO: make real date validation
    private function validateDate(mixed $value): bool
    {
        if (Arr::has($value, 'min') && !is_string($value['min'])) {
            $this->message = "Min value for attribute `{$this->attributeName}` must be a date.";

            return false;
        }

        if (Arr::has($value, 'max') && !is_string($value['max'])) {
            $this->message = "Max value for attribute `{$this->attributeName}` must be a date.";

            return false;
        }

        return true;
    }

    private function validateOptions(mixed $value): bool
    {
        if (!is_array($value)) {
            $value = Str::replace('%2C', ',', $value);
            $value = explode(',', $value);
        }

        foreach ($value as $option) {
            if (!Str::isUuid($option)) {
                $this->message = "Option `{$option}` for attribute `{$this->attributeName}` must be a valid UUID.";

                return false;
            }
        }

        $this->message = "Invalid options for attribute `{$this->attributeName}`.";

        return AttributeOption::query()
            ->where('attribute_id', $this->attribute?->getKey())
            ->whereIn('id', $value)
            ->exists();
    }
}
