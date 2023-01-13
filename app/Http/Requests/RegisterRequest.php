<?php

namespace App\Http\Requests;

use App\Rules\Boolean;
use App\Rules\ConsentExists;
use App\Rules\RequiredConsents;
use App\Traits\BooleanRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use BooleanRules;

    protected array $booleanFields = [
        'consents.*',
    ];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', Password::defaults()],
            'consents.*' => [new ConsentExists(), new Boolean()],
            'consents' => ['array', new RequiredConsents()],
            'birthday_date' => ['date', 'before_or_equal:now'],
            'phone' => ['phone:AUTO'],
            'metadata_personal' => ['array'],
        ];
    }
}
