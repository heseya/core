<?php

namespace App\Services\Contracts;

use App\Models\User;

interface AuthServiceContract
{
    public function login(string $email, string $psswd, ?string $ip, ?string $userAgent);

    public function logout(User $user): void;

    public function resetPassword(string $email): void;

    public function showResetPasswordForm(?string $email, ?string $token): User;

    public function saveResetPassword(string $email, string $token, string $psswd): void;

    public function changePassword(User $user, string $psswd, string $newPsswd): void;

    public function loginHistory(User $user);
}
