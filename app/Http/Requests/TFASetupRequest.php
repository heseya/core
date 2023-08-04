<?php

namespace App\Http\Requests;

use App\Enums\TFAType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TFASetupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(TFAType::class)],
        ];
    }
}
