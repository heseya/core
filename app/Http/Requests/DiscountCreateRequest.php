<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class DiscountCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'unique:discounts'],
            'discount' => ['required', 'numeric'],
            'type' => ['required', new EnumValue(DiscountType::class, false)],
            'max_uses' => ['required', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at']
        ];
    }
}
