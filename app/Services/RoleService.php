<?php

namespace App\Services;

use App\Dtos\RoleCreateDto;
use App\Dtos\RoleSearchDto;
use App\Dtos\RoleUpdateDto;
use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService implements RoleServiceContract
{
    public function search(RoleSearchDto $searchDto, int $limit): LengthAwarePaginator
    {

    }

    public function create(RoleCreateDto $dto): Role
    {

    }

    public function update(Role $page, RoleUpdateDto $dto): Role
    {

    }

    public function delete(Role $page): void
    {

    }
}
