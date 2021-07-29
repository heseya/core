<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Passport\PersonalAccessTokenResult;

interface AuthServiceContract
{
    public function login(string $email, string $password, ?string $ip, ?string $userAgent): PersonalAccessTokenResult;

    public function logout(User $user): void;

    public function resetPassword(string $email): void;

    public function showResetPasswordForm(?string $email, ?string $token): User;

    public function saveResetPassword(string $email, string $token, string $password): void;

    public function changePassword(User $user, string $password, string $newPassword): void;

    public function loginHistory(User $user): Builder;
}
