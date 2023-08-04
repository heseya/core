<?php

namespace App\Http\Requests;

use App\Enums\SavedAddressType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SavedAddressUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'default' => ['nullable', 'boolean'],
            'type' => [new Enum(SavedAddressType::class)],
            'address.name' => ['string', 'max:255'],
            'address.phone' => ['string', 'max:20'],
            'address.address' => ['string', 'max:255'],
            'address.zip' => ['string', 'max:16'],
            'address.city' => ['string', 'max:255'],
            'address.country' => ['string', 'size:2'],
            'address.vat' => ['nullable', 'string', 'max:15'],
        ];
    }
}
