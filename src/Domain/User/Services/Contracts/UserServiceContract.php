<?php

declare(strict_types=1);

namespace Domain\User\Services\Contracts;

use App\Models\User;
use Domain\User\Dtos\UserCreateDto;
use Domain\User\Dtos\UserIndexDto;
use Domain\User\Dtos\UserUpdateDto;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserServiceContract
{
    /**
     * @return LengthAwarePaginator<User>
     */
    public function index(UserIndexDto $dto, ?string $sort): LengthAwarePaginator;

    public function create(UserCreateDto $dto): User;

    public function update(User $user, UserUpdateDto $dto): User;

    public function destroy(User $user): void;
}
