<?php

namespace App\Services\Contracts;

use App\Models\User;

interface AuthServiceContract
{
    public function login(string $email, string $password, ?string $ip, ?string $userAgent): array;

    public function refresh(string $refreshToken, ?string $ip, ?string $userAgent): array;

//    public function logout(User $user): void;

    public function resetPassword(string $email): void;

    public function showResetPasswordForm(?string $email, ?string $token): User;

    public function saveResetPassword(string $email, string $token, string $password): void;

    public function changePassword(User $user, string $password, string $newPassword): void;

//    public function loginHistory(User $user): Builder;
//
//    public function killActiveSession(User $user, string $oauthAccessTokensId);
//
//    public function killAllSessions(User $user);
}
