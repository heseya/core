<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')
                    ->ignoreModel($this->route('user'))
                    ->whereNull('deleted_at'),
            ],
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
