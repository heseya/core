<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserCreateRequest extends FormRequest
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
            'password' => ['required', 'string', 'max:255', Password::defaults()],
            'roles' => ['array'],
            'roles.*' => [
                'uuid',
                Rule::exists('roles', 'id')->where(function ($query) {
                    return $query->whereNotIn('type', [RoleType::AUTHENTICATED, RoleType::UNAUTHENTICATED]);
                }),
            ],
        ];
    }
}
