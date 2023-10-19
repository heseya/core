<?php

namespace App\Dtos;

use App\Enums\RoleType;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class SelfUpdateRoles extends Data
{
    public function __construct(
        public array|Optional $roles = [],
    ) {}

    public function roles(): array
    {
        return [
            'roles.*' => [
                'uuid',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->whereNotIn('type', [RoleType::AUTHENTICATED, RoleType::UNAUTHENTICATED])),
            ],
        ];
    }
}
