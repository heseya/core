<?php

namespace App\Services;

use App\Dtos\RoleCreateDto;
use App\Dtos\RoleSearchDto;
use App\Dtos\RoleUpdateDto;
use App\Models\Role;
use App\Services\Contracts\RoleServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService implements RoleServiceContract
{
    public function search(RoleSearchDto $searchDto, int $limit): LengthAwarePaginator
    {
        return Role::search($searchDto->toArray())->paginate($limit);
    }

    public function create(RoleCreateDto $dto): Role
    {
        $role = Role::create($dto->toArray());

        $role->syncPermissions($dto->getPermissions());

        return $role;
    }

    public function update(Role $role, RoleUpdateDto $dto): Role
    {
        $role->update($dto->toArray());

        if ($dto->getPermissions()) {
            $role->syncPermissions($dto->getPermissions());
        }

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
