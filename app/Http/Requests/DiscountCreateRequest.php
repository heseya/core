<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use BenSampo\Enum\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class DiscountCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'description' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'unique:discounts'],
            'discount' => ['required', 'numeric'],
            'type' => ['required', new Enum(DiscountType::class)],
        ];
    }
}
