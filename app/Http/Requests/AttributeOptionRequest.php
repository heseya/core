<?php

namespace App\Http\Requests;

use App\Enums\AttributeType;
use App\Traits\MetadataRules;
use Illuminate\Foundation\Http\FormRequest;

class AttributeOptionRequest extends FormRequest
{
    use MetadataRules;

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        $nameRule = match ($this->attribute?->type ?? $this->enum('type', AttributeType::class)) {
            AttributeType::SINGLE_OPTION => 'required',
            default => 'nullable',
        };

        return array_merge(
            $this->metadataRules(),
            [
                'id' => ['uuid'],

                'name' => [$nameRule, 'string', 'max:255'],
                'value_number' => ['nullable', 'numeric', 'regex:/^\d{1,6}(\.\d{1,2}|)$/'],
                'value_date' => ['nullable', 'date'],
            ]
        );
    }
}
