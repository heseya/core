<?php

namespace App\Rules;

use Domain\ProductAttribute\Models\AttributeOption;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class AttributeOptionExist implements Rule
{
    private string $attributeId;

    public function passes($attribute, $value)
    {
        $this->attributeId = Str::between($attribute, '.', '.');

        $attributeOption = AttributeOption::where('id', $value)->where('attribute_id', $this->attributeId)->first();

        return $attributeOption !== null;
    }

    public function message()
    {
        return 'This option :value is not available in attribute :attribute';
    }
}
