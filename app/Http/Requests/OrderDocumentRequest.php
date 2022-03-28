<?php

namespace App\Http\Requests;

use App\Enums\OrderDocumentType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class OrderDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'name' => ['string'],
            'type' => ['required', new EnumValue(OrderDocumentType::class, false)],
        ];
    }
}
