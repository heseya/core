<?php

declare(strict_types=1);

namespace Domain\User\Services\Contracts;

use App\Dtos\UserCreateDto;
use App\Dtos\UserDto;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserServiceContract
{
    /**
     * @param array<string, mixed> $search
     *
     * @return LengthAwarePaginator<User>
     */
    public function index(array $search, ?string $sort): LengthAwarePaginator;

    public function create(UserCreateDto $dto): User;

    public function update(User $user, UserDto $dto): User;

    public function destroy(User $user): void;
}
