<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserManagementServiceContract
{
    public function authorize(): void;

    public function index(array $search, ?string $sort, int $limit): LengthAwarePaginator;

    public function create(array $attributes): User;

    public function update(User $user, array $attributes): User;

    public function destroy(User $user): void;
}
