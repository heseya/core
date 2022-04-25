<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class PaymentStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'external_id' => ['required', 'string'],
            'method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'status' => ['required', new EnumValue(PaymentStatus::class, false)],
            'amount' => ['required', 'integer'],
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
        ];
    }
}
