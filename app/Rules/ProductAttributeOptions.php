<?php

namespace App\Rules;

use App\Enums\AttributeType;
use App\Models\Attribute;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class ProductAttributeOptions implements Rule
{
    private string $message;

    public function passes($attribute, $value)
    {
        $attributeModel = Attribute::find(Str::afterLast($attribute, '.'));

        if ($attributeModel === null) {
            $this->message = 'Attribute :attribute not found';
            return false;
        }

        if ($attributeModel->type->is(AttributeType::MULTI_CHOICE_OPTION)) {
            $this->message = 'Attribute :attribute must have at least one option';
            return count($value) >= 1;
        }

        $this->message = 'Attribute :attribute must have one option';
        return count($value) === 1;
    }

    public function message()
    {
        return $this->message;
    }
}
