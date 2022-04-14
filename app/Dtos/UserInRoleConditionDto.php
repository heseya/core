<?php

namespace App\Dtos;

class UserInRoleConditionDto extends ConditionDto
{
    private array $roles;
    private bool $is_allow_list;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            roles: $array['roles'],
            is_allow_list: $array['is_allow_list'],
        );
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isIsAllowList(): bool
    {
        return $this->is_allow_list;
    }
}
