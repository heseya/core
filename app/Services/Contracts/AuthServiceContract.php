<?php

namespace App\Services\Contracts;

use App\Dtos\RegisterDto;
use App\Dtos\SelfUpdateRoles;
use App\Dtos\TFAConfirmDto;
use App\Dtos\TFAPasswordDto;
use App\Dtos\TFASetupDto;
use App\Dtos\UpdateProfileDto;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

interface AuthServiceContract
{
    public function login(string $email, string $password, ?string $ip, ?string $userAgent, ?string $code): array;

    public function loginWithUser(Authenticatable $user, ?string $ip, ?string $userAgent): array;

    public function refresh(string $refreshToken, ?string $ip, ?string $userAgent): array;

    public function logout(): void;

    public function resetPassword(string $email, string $redirect_url): void;

    public function showResetPasswordForm(?string $email, ?string $token): User;

    public function saveResetPassword(string $email, string $token, string $password): void;

    public function changePassword(User $user, string $password, string $newPassword): void;

    public function userByIdentity(string $identityToken): User;

    public function unauthenticatedUser(): User;

    public function isUserAuthenticated(): bool;

    public function isAppAuthenticated(): bool;

    public function setupTFA(TFASetupDto $dto): array;

    public function confirmTFA(TFAConfirmDto $dto): array;

    public function generateRecoveryCodes(TFAPasswordDto $dto): array;

    public function removeTFA(TFAPasswordDto $dto): void;

    public function removeUsersTFA(User $user): void;

    public function register(RegisterDto $dto): User;

    public function updateProfile(UpdateProfileDto $dto): User;

    public function selfRemove(string $password): void;

    public function selfUpdateRoles(SelfUpdateRoles $dto): User;
}
