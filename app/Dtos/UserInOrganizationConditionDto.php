<?php

namespace App\Dtos;

class UserInOrganizationConditionDto extends ConditionDto
{
    private array $organizations;
    private bool $is_allow_list;

    public static function fromArray(array $array): ConditionDto
    {
        return new self(
            type: $array['type'],
            organizations: $array['organizations'],
            is_allow_list: $array['is_allow_list'],
        );
    }

    public function getOrganizations(): array
    {
        return $this->organizations;
    }

    public function isIsAllowList(): bool
    {
        return $this->is_allow_list;
    }
}
