<?php

namespace App\Services\Contracts;

use App\Dtos\UserDto;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserServiceContract
{
    public function index(array $search, ?string $sort): LengthAwarePaginator;

    public function create(UserDto $dto): User;

    public function update(User $user, UserDto $dto): User;

    public function destroy(User $user): void;
}
