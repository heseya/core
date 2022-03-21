<?php

namespace App\Http\Requests;

use App\Enums\AttributeType;
use Illuminate\Foundation\Http\FormRequest;

class AttributeOptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $nameRule = match ($this?->attribute?->type->value ?? $this->input('type')) {
            AttributeType::SINGLE_OPTION => 'required',
            default => 'nullable'
        };

        return [
            'name' => [$nameRule, 'string', 'max:255'],
            'value_number' => ['nullable', 'numeric'],
            'value_date' => ['nullable', 'date'],
        ];
    }
}
