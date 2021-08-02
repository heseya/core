<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserManagementServiceContract
{
    public function index(array $search, ?string $sort, int $limit): LengthAwarePaginator;

    public function create(string $name, string $email, string $password): User;

    public function update(User $user, ?string $name, ?string $email): User;

    public function destroy(User $user): void;
}
