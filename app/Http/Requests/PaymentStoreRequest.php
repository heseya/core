<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PaymentStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'external_id' => ['required', 'string'],
            'method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'status' => ['required', new Enum(PaymentStatus::class)],
            'amount' => ['required', 'numeric'],
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
        ];
    }
}
