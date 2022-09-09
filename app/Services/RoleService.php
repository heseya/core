<?php

namespace App\Services;

use App\Dtos\RoleCreateDto;
use App\Dtos\RoleSearchDto;
use App\Dtos\RoleUpdateDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Exceptions\ClientException;
use App\Models\Role;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\RoleServiceContract;
use Heseya\Dto\Missing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class RoleService implements RoleServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
    ) {
    }

    public function search(RoleSearchDto $searchDto): LengthAwarePaginator
    {
        return Role::searchByCriteria($searchDto->toArray())
            ->withCount('users')
            ->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @throws ClientException
     */
    public function create(RoleCreateDto $dto): Role
    {
        if (!Auth::user()->hasAllPermissions($dto->getPermissions())) {
            throw new ClientException(Exceptions::CLIENT_CREATE_ROLE_WITHOUT_PERMISSION);
        }

        /** @var Role $role */
        $role = Role::create($dto->toArray());
        $role->syncPermissions($dto->getPermissions());

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($role, $dto->getMetadata());
        }

        $role->refresh();

        return $role;
    }

    /**
     * @throws ClientException
     */
    public function update(Role $role, RoleUpdateDto $dto): Role
    {
        $user = Auth::user();

        if (!$user->hasAllPermissions($role->getAllPermissions())) {
            throw new ClientException(Exceptions::CLIENT_UPDATE_ROLE_WITHOUT_PERMISSION);
        }

        if (!$dto->getPermissions() instanceof Missing) {
            if (
                $role->type->is(RoleType::OWNER) &&
                $role->getPermissionNames()->diff($dto->getPermissions())->isNotEmpty()
            ) {
                throw new ClientException(Exceptions::CLIENT_UPDATE_OWNER_PERMISSION);
            }

            if (!$user->hasAllPermissions($dto->getPermissions())) {
                throw new ClientException(Exceptions::CLIENT_UPDATE_ROLE_WITHOUT_PERMISSION);
            }

            $role->syncPermissions($dto->getPermissions());
        }

        $role->update($dto->toArray());

        return $role;
    }

    /**
     * @throws ClientException
     */
    public function delete(Role $role): void
    {
        if (
            $role->type->is(RoleType::OWNER) ||
            $role->type->is(RoleType::UNAUTHENTICATED) ||
            $role->type->is(RoleType::AUTHENTICATED)
        ) {
            throw new ClientException(Exceptions::CLIENT_DELETE_BUILT_IN_ROLE);
        }

        if (!Auth::user()->hasAllPermissions($role->getAllPermissions())) {
            throw new ClientException(Exceptions::CLIENT_DELETE_ROLE_WITHOUT_PERMISSION);
        }

        $role->delete();
    }
}
