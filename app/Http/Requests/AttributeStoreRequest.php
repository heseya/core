<?php

namespace App\Http\Requests;

use App\Enums\AttributeType;
use App\Rules\Boolean;
use App\Traits\BooleanRules;
use App\Traits\MetadataRules;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class AttributeStoreRequest extends FormRequest
{
    use BooleanRules, MetadataRules;

    protected array $booleanFields = [
        'global',
        'sortable',
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'unique:attributes'],
                'description' => ['nullable', 'string', 'max:255'],
                'type' => ['required', new EnumValue(AttributeType::class, false)],
                'global' => ['required', new Boolean()],
                'sortable' => ['required', new Boolean()],
                'options' => ['nullable', 'array'],
            ]
        );
    }
}
