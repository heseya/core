<?php

namespace App\Http\Requests;

use App\Enums\SavedAddressType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class SavedAddressStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'default' => ['required', 'boolean'],
            'type' => [new EnumValue(SavedAddressType::class)],
            'address.name' => ['required', 'string', 'max:255'],
            'address.phone' => ['required', 'string', 'max:20'],
            'address.address' => ['required', 'string', 'max:255'],
            'address.zip' => ['required', 'string', 'max:16'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.country' => ['required', 'string', 'size:2'],
            'address.vat' => ['nullable', 'string', 'max:15'],
        ];
    }
}
