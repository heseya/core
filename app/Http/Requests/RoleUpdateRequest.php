<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => ['string', Rule::unique('roles')->ignore($role->getKey(), $role->getKeyName())],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
