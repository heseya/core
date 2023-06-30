<?php

namespace App\Http\Requests;

use App\Enums\AttributeType;
use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AttributeStoreRequest extends FormRequest
{
    use MetadataRules;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $attributeOptionRequest = new AttributeOptionRequest($this->all());
        $optionRules = [];

        foreach ($attributeOptionRequest->rules() as $field => $rules) {
            $optionRules['options.*.' . $field] = $rules;
        }

        return array_merge(
            $optionRules,
            $this->metadataRules(),
            [
                'id' => ['nullable', 'uuid'],

                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'unique:attributes'],
                'description' => ['nullable', 'string', 'max:255'],
                'type' => ['required', new Enum(AttributeType::class)],
                'global' => ['required', 'boolean'],
                'sortable' => ['required', 'boolean'],
            ]
        );
    }
}
