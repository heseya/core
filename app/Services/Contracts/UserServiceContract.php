<?php

namespace App\Services\Contracts;

use App\Dtos\SelfUpdateRoles;
use App\Dtos\UserCreateDto;
use App\Dtos\UserDto;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserServiceContract
{
    public function index(array $search, ?string $sort): LengthAwarePaginator;

    public function create(UserCreateDto $dto): User;

    public function update(User $user, UserDto $dto): User;

    public function destroy(User $user): void;

    public function selfUpdateRoles(User $user, SelfUpdateRoles $dto): User;
}
