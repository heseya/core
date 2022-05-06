<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Rules\ConsentExists;
use App\Rules\RequiredConsentsUpdate;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'consents.*' => [new ConsentExists(), new Boolean()],
            'consents' => ['nullable', 'array', new RequiredConsentsUpdate()],
        ];
    }
}
