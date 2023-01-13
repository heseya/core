<?php

namespace App\Http\Requests;

use App\Enums\AttributeType;
use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class AttributeOptionRequest extends FormRequest
{
    use MetadataRules;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        $nameRule = match ($this->attribute?->type->value ?? $this->input('type')) {
            AttributeType::SINGLE_OPTION => 'required',
            default => 'nullable',
        };

        return array_merge(
            $this->metadataRules(),
            [
                'name' => [$nameRule, 'string', 'max:255'],
                'value_number' => ['nullable', 'numeric', 'regex:/^\d{1,6}(\.\d{1,2}|)$/'],
                'value_date' => ['nullable', 'date'],
            ]
        );
    }
}
