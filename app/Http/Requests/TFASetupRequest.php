<?php

namespace App\Http\Requests;

use App\Enums\TFAType;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class TFASetupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', new EnumValue(TFAType::class, false)],
        ];
    }
}
