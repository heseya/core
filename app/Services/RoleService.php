<?php

namespace App\Services;

use App\Dtos\RoleCreateDto;
use App\Dtos\RoleSearchDto;
use App\Dtos\RoleUpdateDto;
use App\Exceptions\AuthException;
use App\Models\Role;
use App\Services\Contracts\RoleServiceContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class RoleService implements RoleServiceContract
{
    public function search(RoleSearchDto $searchDto, int $limit): LengthAwarePaginator
    {
        return Role::search($searchDto->toArray())->paginate($limit);
    }

    public function create(RoleCreateDto $dto): Role
    {
        $role = Role::create($dto->toArray());

        if (!Auth::user()->hasAllPermissions($dto->getPermissions())) {
            throw new AuthException(
                'Cant create a role with permissions you don\'t have',
            );
        }

        $role->syncPermissions($dto->getPermissions());

        return $role;
    }

    public function update(Role $role, RoleUpdateDto $dto): Role
    {
        if (!Auth::user()->hasAllPermissions($role->getAllPermissions())) {
            throw new AuthException(
                'Cant update a role with permissions you don\'t have',
            );
        }

        if ($dto->getPermissions()) {
            if (!Auth::user()->hasAllPermissions($dto->getPermissions())) {
                throw new AuthException(
                    'Cant update a role with permissions you don\'t have',
                );
            }

            $role->syncPermissions($dto->getPermissions());
        }

        $role->update($dto->toArray());

        return $role;
    }

    public function delete(Role $role): void
    {
        if (!Auth::user()->hasAllPermissions($role->getAllPermissions())) {
            throw new AuthException(
                'Cant delete a role with permissions you don\'t have',
            );
        }

        $role->delete();
    }


}
