<?php

namespace App\Http\Requests;

use App\Enums\AttributeType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class AttributeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $attributeOptionRequest = new AttributeOptionRequest();
        $optionRules = [];

        foreach ($attributeOptionRequest->rules() as $field => $rules) {
            $optionRules['options.*.' . $field] = $rules;
        }

        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', new EnumValue(AttributeType::class, false)],
            'searchable' => ['required', 'boolean'],
            'options' => ['required', 'array'],
        ], $optionRules);
    }
}
