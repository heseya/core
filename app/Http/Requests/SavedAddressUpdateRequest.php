<?php

namespace App\Http\Requests;

use App\Enums\SavedAddressType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

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
            'type' => [new EnumValue(SavedAddressType::class)],
            'address.name' => ['string', 'max:255'],
            'address.phone' => ['string', 'max:20'],
            'address.address' => ['string', 'max:255'],
            'address.zip' => ['string', 'max:16'],
            'address.city' => ['string', 'max:255'],
            'address.country' => ['string', 'size:2'],
            'address.vat' => ['string', 'max:15'],
        ];
    }
}
