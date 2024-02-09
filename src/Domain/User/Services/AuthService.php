<?php

declare(strict_types=1);

namespace Domain\User\Services;

use App\DTO\Auth\RegisterDto;
use App\Dtos\SelfUpdateRoles;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Enums\TFAType;
use App\Enums\TokenType;
use App\Events\PasswordReset;
use App\Events\TfaInit;
use App\Events\TfaSecurityCode as TfaSecurityCodeEvent;
use App\Events\UserCreated;
use App\Exceptions\AuthException;
use App\Exceptions\ClientException;
use App\Exceptions\TFAException;
use App\Models\App;
use App\Models\Role;
use App\Models\Token;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\ResetPassword;
use App\Notifications\TFAInitialization;
use App\Notifications\TFASecurityCode;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use App\Services\Contracts\TokenServiceContract;
use Domain\Consent\Services\ConsentService;
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
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use PHPGangsta_GoogleAuthenticator;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\LaravelData\Optional;

final class AuthService
{
    public function __construct(
        protected TokenServiceContract $tokenService,
        protected OneTimeSecurityCodeContract $oneTimeSecurityCodeService,
        protected ConsentService $consentService,
        protected UserLoginAttemptService $userLoginAttemptService,
        protected UserService $userService,
        protected MetadataServiceContract $metadataService,
    ) {}

    /**
     * @return array<string, bool|string>
     *
     * @throws ClientException
     */
    public function login(LoginDto $dto): array
    {
        $uuid = Str::uuid()->toString();

        $token = Auth::claims([
            'iss' => Config::get('app.url'),
            'typ' => TokenType::ACCESS->value,
            'jti' => $uuid,
        ])->attempt($dto->only('email', 'password')->toArray());

        if ($token === false) {
            $this->userLoginAttemptService->store();
            throw new ClientException(Exceptions::CLIENT_INVALID_CREDENTIALS, simpleLogs: true);
        }

        $this->verifyTFA($dto->code);

        $this->userLoginAttemptService->store(true);

        return $this->createTokens($token, $uuid);
    }

    /**
     * @return array<string, bool|string>
     */
    public function loginWithUser(Authenticatable $user, ?string $ip, ?string $userAgent): array
    {
        $uuid = Str::uuid()->toString();

        Auth::claims([
            'iss' => Config::get('app.url'),
            'typ' => TokenType::ACCESS->value,
            'jti' => $uuid,
        ]);
        Auth::login($user);
        /** @phpstan-ignore-next-line */
        $token = Auth::fromUser($user);

        $this->userLoginAttemptService->store(true);

        return $this->createTokens($token, $uuid);
    }

    /**
     * @return array<string, string|null>
     */
    public function refresh(TokenRefreshDto $dto): array
    {
        if (!$this->tokenService->validate($dto->refresh_token)) {
            throw new ClientException(Exceptions::CLIENT_INVALID_TOKEN);
        }

        $payload = $this->tokenService->payload($dto->refresh_token);

        if (
            $payload?->get('typ') !== TokenType::REFRESH->value
            || Token::where('id', $payload->get('jti'))->where('invalidated', true)->exists()
        ) {
            throw new ClientException(Exceptions::CLIENT_INVALID_TOKEN);
        }

        $uuid = Str::uuid()->toString();
        /** @var JWTSubject|null $user */
        $user = $this->tokenService->getUser($dto->refresh_token);
        $this->tokenService->invalidateToken($dto->refresh_token);

        if ($user === null) {
            throw new ClientException(Exceptions::CLIENT_USER_DOESNT_EXIST);
        }

        $token = $this->tokenService->createToken(
            $user,
            TokenType::ACCESS,
            $uuid,
        );
        $identityToken = $user instanceof App ? null : $this->tokenService->createToken(
            $user,
            TokenType::IDENTITY,
            $uuid,
        );
        $refreshToken = $this->tokenService->createToken(
            $user,
            TokenType::REFRESH,
            $uuid,
        );

        return [
            'token' => $token,
            'identity_token' => $identityToken,
            'refresh_token' => $refreshToken,
        ];
    }

    public function logout(): void
    {
        // @phpstan-ignore-next-line
        $this->tokenService->invalidateToken(Auth::getToken()->get());
    }

    public function resetPassword(PasswordResetDto $dto): void
    {
        $user = User::whereEmail($dto->email)->first();
        if ($user) {
            $token = Password::createToken($user);

            PasswordReset::dispatch($user, $dto->redirect_url);
            $user->notify(new ResetPassword($token, $dto->redirect_url));
        }
    }

    /**
     * @throws ClientException
     */
    public function showResetPasswordForm(ShowResetPasswordFormDto $dto): User
    {
        if (!$dto->token) {
            throw new ClientException(Exceptions::CLIENT_INVALID_TOKEN);
        }

        $user = $this->getUserByEmail($dto->email);
        $this->checkPasswordResetToken($user, $dto->token);

        return $user;
    }

    /**
     * @throws ClientException
     */
    public function saveResetPassword(PasswordResetSaveDto $dto): void
    {
        $user = $this->getUserByEmail($dto->email);
        $this->checkPasswordResetToken($user, $dto->token);

        $user->update([
            'password' => Hash::make($dto->password),
        ]);

        Password::deleteToken($user);
    }

    /**
     * @throws ClientException
     */
    public function changePassword(User $user, ChangePasswordDto $dto): void
    {
        $this->checkCredentials($user, $dto->password);

        $user->update([
            'password' => Hash::make($dto->password_new),
        ]);
    }

    /**
     * @throws AuthException
     */
    public function userByIdentity(string $identityToken): User
    {
        $user = $this->tokenService->getUser($identityToken);
        $isIdentityToken = $this->tokenService->isTokenType(
            $identityToken,
            TokenType::IDENTITY,
        );

        if (!($user instanceof User) || !$isIdentityToken) {
            throw new ClientException(Exceptions::CLIENT_INVALID_IDENTITY_TOKEN);
        }

        return $user;
    }

    public function unauthenticatedUser(): User
    {
        $user = new User([
            'name' => 'Unauthenticated',
        ]);

        $roles = Role::where('type', RoleType::UNAUTHENTICATED->value)->get();
        $user->setRelation('roles', $roles);
        $user->setAttribute('id', null);

        return $user;
    }

    public function isAppAuthenticated(): bool
    {
        // @phpstan-ignore-next-line
        return Auth::user() instanceof App;
    }

    /**
     * @return array<string, string|int>
     *
     * @throws ClientException
     */
    public function setupTFA(TFASetupDto $dto): array
    {
        $this->checkIsUserTFA();
        /** @var User $user */
        $user = Auth::user();
        $this->checkIsTFA($user);

        return match ($dto->type) {
            TFAType::APP => $this->googleTFA(),
            TFAType::EMAIL => $this->emailTFA(),
        };
    }

    public function isUserAuthenticated(): bool
    {
        return Auth::user() instanceof User;
    }

    /**
     * @return array<string>
     *
     * @throws ClientException
     */
    public function confirmTFA(TFAConfirmDto $dto): array
    {
        $this->checkIsUserTFA();
        /** @var User $user */
        $user = Auth::user();
        $this->checkIsTFA($user);

        if (!$user->tfa_type) {
            throw new ClientException(Exceptions::CLIENT_DOESNT_HAVE_TFA_TYPE_SELECTED);
        }

        $this->checkIsValidTFA($dto->code);

        $user->update([
            'is_tfa_active' => true,
        ]);

        return $this->oneTimeSecurityCodeService->generateRecoveryCodes();
    }

    /**
     * @return array<string>
     *
     * @throws ClientException
     */
    public function generateRecoveryCodes(TFAPasswordDto $dto): array
    {
        $user = $dto->user;
        $this->checkCredentials($user, $dto->password);

        $this->checkNoTFA($user);

        return $this->oneTimeSecurityCodeService->generateRecoveryCodes();
    }

    /**
     * @throws ClientException
     */
    public function removeTFA(TFAPasswordDto $dto): void
    {
        $user = $dto->user;
        $this->checkCredentials($user, $dto->password);

        $this->checkNoTFA($user);

        $this->removeUserTFAData($user);
    }

    public function removeUsersTFA(User $user): void
    {
        $this->checkNoTFA($user);

        $this->removeUserTFAData($user);
    }

    /**
     * @throws ClientException
     */
    public function register(RegisterDto $dto): User
    {
        $fields = $dto->toArray();
        $fields['password'] = Hash::make($dto->password);

        if (!($dto->phone instanceof Optional)) {
            $phone = new PhoneNumber($dto->phone);
            $fields['phone_number'] = $phone->formatNational();
            $fields['phone_country'] = $phone->getCountry();
        }

        $authenticated = Role::query()->where('type', RoleType::AUTHENTICATED->value)->first();
        $roleModels = Role::query()->whereIn('id', $dto->roles)->get();

        $nonRegistrationRoles = $roleModels->filter(fn (Role $role) => !$role->is_registration_role);

        if ($nonRegistrationRoles->isNotEmpty()) {
            throw new ClientException(Exceptions::CLIENT_REGISTER_WITH_NON_REGISTRATION_ROLE);
        }

        /** @var User $user */
        $user = User::query()->create($fields);
        $user->syncRoles([$authenticated, ...$roleModels]);

        $this->consentService->syncUserConsents($user, $dto->consents);

        $preferences = UserPreference::query()->create();
        $preferences->refresh();

        $user->preferences()->associate($preferences);

        if (!($dto->metadata_personal instanceof Optional)) {
            $this->metadataService->sync($user, $dto->metadata_personal);
        }

        $user->save();

        UserCreated::dispatch($user);

        return $user;
    }

    public function updateProfile(ProfileUpdateDto $dto): User
    {
        /** @var User $user */
        $user = Auth::user();
        $data = $dto->toArray();
        // set as null because in DTO phone country and number are empty string if phone is null
        if (!$dto->phone) {
            $data['phone_country'] = null;
            $data['phone_number'] = null;
        }
        $user->update($data);

        $user->preferences()->update($dto->preferences instanceof Optional ? [] : $dto->preferences->toArray());

        if (!($dto->consents instanceof Optional)) {
            $this->consentService->updateUserConsents(Collection::make($dto->consents), $user);
        }

        return $user;
    }

    /**
     * @throws ClientException
     */
    public function selfRemove(string $password): void
    {
        if ($this->isAppAuthenticated()) {
            throw new ClientException(Exceptions::CLIENT_APPS_NO_ACCESS);
        }

        /** @var User $user */
        $user = Auth::user();

        $this->checkCredentials($user, $password);

        $this->userService->destroy($user);
    }

    public function selfUpdateRoles(SelfUpdateRoles $dto): User
    {
        if ($this->isAppAuthenticated()) {
            throw new ClientException(Exceptions::CLIENT_APPS_NO_ACCESS);
        }

        /** @var User $user */
        $user = Auth::user();

        return $this->userService->selfUpdateRoles($user, $dto);
    }

    /**
     * @throws ClientException
     */
    private function verifyTFA(Optional|string|null $code): void
    {
        if (!Auth::user()?->is_tfa_active && !($code instanceof Optional) && $code !== null) {
            $this->userLoginAttemptService->store();
            throw new ClientException(Exceptions::CLIENT_TFA_NOT_SET_UP, simpleLogs: true);
        }

        if (Auth::user()?->is_tfa_active) {
            if ($code instanceof Optional || $code === null) {
                $this->noTFACode();
            } else {
                $this->checkIsValidTFA($code);
            }
        }
    }

    private function noTFACode(): void
    {
        if (Auth::user()?->tfa_type === TFAType::EMAIL) {
            Auth::user()->securityCodes()->where('expires_at', '!=', null)->delete();
            $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode(
                Auth::user(),
                Config::get('tfa.code_expires_time'),
            );

            TfaSecurityCodeEvent::dispatch(Auth::user(), $code);
            Auth::user()->notify(new TFASecurityCode($code));
        }
        throw new ClientException(Exceptions::CLIENT_TFA_REQUIRED, simpleLogs: true, errorArray: ['type' => Auth::user()?->tfa_type]);
    }

    private function checkIsValidTFA(string $code): void
    {
        /** @var User $user */
        $user = Auth::user();
        $valid = match ($user->tfa_type) {
            TFAType::APP => $this->googleTFAVerify($code),
            TFAType::EMAIL => $this->emailTFAVerify($code),
            default => $this->invalidTFAType(),
        };

        if ($user->is_tfa_active && !$valid) {
            $valid = $this->verifyRecoveryCode($code);
        }

        if (!$valid) {
            $this->userLoginAttemptService->store();
            throw new ClientException(Exceptions::CLIENT_TFA_INVALID_TOKEN, simpleLogs: true);
        }
    }

    private function googleTFAVerify(string $code): bool
    {
        $google_authenticator = new PHPGangsta_GoogleAuthenticator();

        /** @var User $user */
        $user = Auth::user();

        return $user->tfa_secret !== null && $google_authenticator->verifyCode($user->tfa_secret, $code);
    }

    private function emailTFAVerify(string $code): bool
    {
        $security_codes = Auth::user()?->securityCodes()
            ->where('expires_at', '>', Carbon::now())->get();

        if ($security_codes !== null) {
            foreach ($security_codes as $security_code) {
                if (Hash::check($code, $security_code->code)) {
                    $security_code->delete();

                    return true;
                }
            }
        }

        return false;
    }

    private function invalidTFAType(): void
    {
        $this->userLoginAttemptService->store();
        throw new TFAException('Invalid Two-Factor Authentication Type', simpleLogs: true);
    }

    private function verifyRecoveryCode(string $code): bool
    {
        $security_codes = Auth::user()?->securityCodes()
            ->whereNull('expires_at')->get();

        $security_codes ??= [];
        foreach ($security_codes as $security_code) {
            if (Hash::check($code, $security_code->code)) {
                $security_code->delete();
                Auth::user()?->securityCodes()->whereNotNull('expires_at')->delete();

                return true;
            }
        }

        return false;
    }

    private function getUserByEmail(?string $email): User
    {
        $user = User::whereEmail($email)->first();
        if (!$user) {
            throw new ClientException(Exceptions::CLIENT_USER_DOESNT_EXIST, simpleLogs: true);
        }

        return $user;
    }

    private function checkPasswordResetToken(User $user, string $token): void
    {
        if (!Password::tokenExists($user, $token)) {
            throw new ClientException(Exceptions::CLIENT_TOKEN_INVALID_OR_INACTIVE, simpleLogs: true);
        }
    }

    private function checkCredentials(User $user, string $password): void
    {
        /** @var string $userPassword */
        $userPassword = $user->password;
        if (!Hash::check($password, $userPassword)) {
            throw new ClientException(Exceptions::CLIENT_INVALID_PASSWORD, simpleLogs: true);
        }
    }

    private function checkIsUserTFA(): void
    {
        if (!$this->isUserAuthenticated()) {
            throw new ClientException(Exceptions::CLIENT_ONLY_USER_CAN_SET_TFA);
        }
    }

    private function checkIsTFA(User $user): void
    {
        if ($user->is_tfa_active) {
            throw new ClientException(Exceptions::CLIENT_TFA_ALREADY_SET_UP);
        }
    }

    /**
     * @return array<string, string>
     *
     * @throws Exception
     */
    private function googleTFA(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $google_authenticator = new PHPGangsta_GoogleAuthenticator();

        $secret = $google_authenticator->createSecret();
        $qr_code_url = $google_authenticator->getQRCodeGoogleUrl(
            $user->email,
            $secret,
            Config::get('app.name'),
        );

        $user->update([
            'tfa_type' => TFAType::APP,
            'tfa_secret' => $secret,
        ]);

        return [
            'type' => TFAType::APP->value,
            'secret' => $secret,
            'qr_code_url' => $qr_code_url,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function emailTFA(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $user->securityCodes()->delete();
        $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode(
            $user,
            Config::get('tfa.code_expires_time'),
        );

        $user->update([
            'tfa_type' => TFAType::EMAIL,
        ]);

        TfaInit::dispatch($user, $code);
        $user->notify(new TFAInitialization($code));

        return [
            'type' => TFAType::EMAIL->value,
        ];
    }

    private function checkNoTFA(User $user): void
    {
        if (!$user->is_tfa_active) {
            throw new ClientException(Exceptions::CLIENT_TFA_NOT_SET_UP);
        }
    }

    private function removeUserTFAData(User $user): void
    {
        $user->securityCodes()->delete();

        $user->update([
            'tfa_type' => null,
            'tfa_secret' => null,
            'is_tfa_active' => false,
        ]);
    }

    /**
     * @return array<string, bool|string>
     */
    private function createTokens(bool|string $token, string $uuid): array
    {
        /** @var JWTSubject $user */
        $user = Auth::user();
        $identityToken = $this->tokenService->createToken(
            $user,
            TokenType::IDENTITY,
            $uuid,
        );
        $refreshToken = $this->tokenService->createToken(
            $user,
            TokenType::REFRESH,
            $uuid,
        );

        return [
            'token' => $token,
            'identity_token' => $identityToken,
            'refresh_token' => $refreshToken,
        ];
    }
}
