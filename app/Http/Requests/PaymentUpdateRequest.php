<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class PaymentUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'external_id' => ['string'],
            'method_id' => ['uuid', 'exists:payment_methods,id'],
            'status' => [new EnumValue(PaymentStatus::class, false)],
            'amount' => ['float'],
        ];
    }
}
