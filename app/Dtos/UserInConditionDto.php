<?php

namespace App\Dtos;

class UserInConditionDto extends ConditionDto
{
    private array $users;
    private bool $is_allow_list;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            users: $array['users'],
            is_allow_list: $array['is_allow_list'],
        );
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function isIsAllowList(): bool
    {
        return $this->is_allow_list;
    }
}
