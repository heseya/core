<?php

namespace App\Services\Contracts;

use App\Models\User;
use Domain\User\Dtos\UserCreateDto;
use Domain\User\Dtos\UserUpdateDto;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserServiceContract
{
    public function index(array $search, ?string $sort): LengthAwarePaginator;

    public function create(UserCreateDto $dto): User;

    public function update(User $user, UserUpdateDto $dto): User;

    public function destroy(User $user): void;
}
