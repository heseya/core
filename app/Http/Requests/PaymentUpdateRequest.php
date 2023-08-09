<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PaymentUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'external_id' => ['string'],
            'method_id' => ['uuid', 'exists:payment_methods,id'],
            'status' => [new Enum(PaymentStatus::class)],
            'amount' => ['numeric'],
        ];
    }
}
