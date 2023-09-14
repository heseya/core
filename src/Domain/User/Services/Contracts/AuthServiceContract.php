<?php

declare(strict_types=1);

namespace Domain\User\Services\Contracts;

use App\DTO\Auth\RegisterDto;
use App\Models\User;
use Domain\User\Dtos\ChangePasswordDto;
use Domain\User\Dtos\LoginDto;
use Domain\User\Dtos\PasswordResetDto;
use Domain\User\Dtos\PasswordResetSaveDto;
use Domain\User\Dtos\ProfileUpdateDto;
use Domain\User\Dtos\ShowResetPasswordFormDto;
use Domain\User\Dtos\TFAConfirmDto;
use Domain\User\Dtos\TFAPasswordDto;
use Domain\User\Dtos\TFASetupDto;
use Domain\User\Dtos\TokenRefreshDto;
use Illuminate\Contracts\Auth\Authenticatable;

interface AuthServiceContract
{
    /**
     * @return array<string, bool|string>
     */
    public function login(LoginDto $dto): array;

    /**
     * @return array<string, bool|string>
     */
    public function loginWithUser(Authenticatable $user, ?string $ip, ?string $userAgent): array;

    /**
     * @return array<string, string|null>
     */
    public function refresh(TokenRefreshDto $dto): array;

    public function logout(): void;

    public function resetPassword(PasswordResetDto $dto): void;

    public function showResetPasswordForm(ShowResetPasswordFormDto $dto): User;

    public function saveResetPassword(PasswordResetSaveDto $dto): void;

    public function changePassword(User $user, ChangePasswordDto $dto): void;

    public function userByIdentity(string $identityToken): User;

    public function unauthenticatedUser(): User;

    public function isUserAuthenticated(): bool;

    public function isAppAuthenticated(): bool;

    /**
     * @return array<string, string|int>
     */
    public function setupTFA(TFASetupDto $dto): array;

    /**
     * @return array<string>
     */
    public function confirmTFA(TFAConfirmDto $dto): array;

    /**
     * @return array<string>
     */
    public function generateRecoveryCodes(TFAPasswordDto $dto): array;

    public function removeTFA(TFAPasswordDto $dto): void;

    public function removeUsersTFA(User $user): void;

    public function register(RegisterDto $dto): User;

    public function updateProfile(ProfileUpdateDto $dto): User;

    public function selfRemove(string $password): void;
}
