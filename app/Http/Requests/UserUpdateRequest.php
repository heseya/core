<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')
                    ->ignoreModel($user)
                    ->whereNull('deleted_at'),
            ],
            'roles' => ['array'],
            'roles.*' => [
                'uuid',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->whereNotIn('type', [RoleType::AUTHENTICATED, RoleType::UNAUTHENTICATED])),
            ],
            'birthday_date' => ['nullable', 'date', 'before_or_equal:now'],
            'phone' => ['nullable', 'phone:AUTO'],
        ];
    }
}
