<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use App\Rules\ConsentExists;
use App\Rules\RequiredConsents;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
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
            'consents.*' => [new ConsentExists(), 'boolean'],
            'consents' => ['array', new RequiredConsents()],
            'roles' => ['array'],
            'roles.*' => [
                'uuid',
                Rule::exists('roles', 'id')->where(function ($query) {
                    return $query->whereNotIn('type', [RoleType::AUTHENTICATED, RoleType::UNAUTHENTICATED]);
                }),
            ],
            'birthday_date' => ['date', 'before_or_equal:now'],
            'phone' => ['phone:AUTO'],
            'metadata_personal' => ['array'],
        ];
    }
}
