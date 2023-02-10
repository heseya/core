<?php

namespace App\Services;

use App\Dtos\RegisterDto;
use App\Dtos\TFAConfirmDto;
use App\Dtos\TFAPasswordDto;
use App\Dtos\TFASetupDto;
use App\Dtos\UpdateProfileDto;
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
use App\Notifications\UserRegistered;
use App\Services\Contracts\AuthServiceContract;
use App\Services\Contracts\ConsentServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use App\Services\Contracts\TokenServiceContract;
use App\Services\Contracts\UserLoginAttemptServiceContract;
use Heseya\Dto\Missing;
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

class AuthService implements AuthServiceContract
{
    public function __construct(
        protected TokenServiceContract $tokenService,
        protected OneTimeSecurityCodeContract $oneTimeSecurityCodeService,
        protected ConsentServiceContract $consentService,
        protected UserLoginAttemptServiceContract $userLoginAttemptService,
        private MetadataServiceContract $metadataService,
    ) {
    }

    public function login(string $email, string $password, ?string $ip, ?string $userAgent, ?string $code): array
    {
        $uuid = Str::uuid()->toString();

        $token = Auth::claims([
            'iss' => Config::get('app.url'),
            'typ' => TokenType::ACCESS,
            'jti' => $uuid,
        ])->attempt([
            'email' => $email,
            'password' => $password,
        ]);

        if ($token === false) {
            $this->userLoginAttemptService->store();
            throw new ClientException(Exceptions::CLIENT_INVALID_CREDENTIALS, simpleLogs: true);
        }

        $this->verifyTFA($code);

        $this->userLoginAttemptService->store(true);

        return $this->createTokens($token, $uuid);
    }

    public function loginWithUser(Authenticatable $user, ?string $ip, ?string $userAgent): array
    {
        $uuid = Str::uuid()->toString();

        Auth::claims([
            'iss' => Config::get('app.url'),
            'typ' => TokenType::ACCESS,
            'jti' => $uuid,
        ]);
        // @phpstan-ignore-next-line
        Auth::login($user);
        // @phpstan-ignore-next-line
        $token = Auth::fromUser($user);

        $this->userLoginAttemptService->store(true);

        return $this->createTokens($token, $uuid);
    }

    public function refresh(string $refreshToken, ?string $ip, ?string $userAgent): array
    {
        if (!$this->tokenService->validate($refreshToken)) {
            throw new ClientException(Exceptions::CLIENT_INVALID_TOKEN);
        }

        $payload = $this->tokenService->payload($refreshToken);

        if (
            $payload?->get('typ') !== TokenType::REFRESH ||
            Token::where('id', $payload->get('jti'))->where('invalidated', true)->exists()
        ) {
            throw new ClientException(Exceptions::CLIENT_INVALID_TOKEN);
        }

        $uuid = Str::uuid()->toString();
        /** @var JWTSubject|null $user */
        $user = $this->tokenService->getUser($refreshToken);
        $this->tokenService->invalidateToken($refreshToken);

        if ($user === null) {
            throw new ClientException(Exceptions::CLIENT_USER_DOESNT_EXIST);
        }

        $token = $this->tokenService->createToken(
            $user,
            new TokenType(TokenType::ACCESS),
            $uuid,
        );
        $identityToken = $user instanceof App ? null : $this->tokenService->createToken(
            $user,
            new TokenType(TokenType::IDENTITY),
            $uuid,
        );
        $refreshToken = $this->tokenService->createToken(
            $user,
            new TokenType(TokenType::REFRESH),
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
        $this->tokenService->invalidateToken(Auth::getToken());
    }

    public function resetPassword(string $email, string $redirect_url): void
    {
        $user = User::whereEmail($email)->first();
        if ($user) {
            $token = Password::createToken($user);

            PasswordReset::dispatch($user, $redirect_url);
            $user->notify(new ResetPassword($token, $redirect_url));
        }
    }

    public function showResetPasswordForm(?string $email, ?string $token): User
    {
        if (!$token) {
            throw new ClientException(Exceptions::CLIENT_INVALID_TOKEN);
        }

        $user = $this->getUserByEmail($email);
        $this->checkPasswordResetToken($user, $token);

        return $user;
    }

    public function saveResetPassword(string $email, string $token, string $password): void
    {
        $user = $this->getUserByEmail($email);
        $this->checkPasswordResetToken($user, $token);

        $user->update([
            'password' => Hash::make($password),
        ]);

        Password::deleteToken($user);
    }

    public function changePassword(User $user, string $password, string $newPassword): void
    {
        $this->checkCredentials($user, $password);

        $user->update([
            'password' => Hash::make($newPassword),
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
            new TokenType(TokenType::IDENTITY),
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

        $roles = Role::where('type', RoleType::UNAUTHENTICATED)->get();
        $user->setRelation('roles', $roles);
        $user->setAttribute('id', null);

        return $user;
    }

    public function isAppAuthenticated(): bool
    {
        // @phpstan-ignore-next-line
        return Auth::user() instanceof App;
    }

    public function setupTFA(TFASetupDto $dto): array
    {
        $this->checkIsUserTFA();
        /** @var User $user */
        $user = Auth::user();
        $this->checkIsTFA($user);

        return match ($dto->getType()) {
            TFAType::APP => $this->googleTFA(),
            TFAType::EMAIL => $this->emailTFA(),
            default => throw new ClientException(Exceptions::CLIENT_INVALID_TFA_TYPE),
        };
    }

    public function isUserAuthenticated(): bool
    {
        return Auth::user() instanceof User;
    }

    public function confirmTFA(TFAConfirmDto $dto): array
    {
        $this->checkIsUserTFA();
        /** @var User $user */
        $user = Auth::user();
        $this->checkIsTFA($user);

        if (!$user->tfa_type) {
            throw new ClientException(Exceptions::CLIENT_DOESNT_HAVE_TFA_TYPE_SELECTED);
        }

        $this->checkIsValidTFA($dto->getCode());

        $user->update([
            'is_tfa_active' => true,
        ]);

        return $this->oneTimeSecurityCodeService->generateRecoveryCodes();
    }

    public function generateRecoveryCodes(TFAPasswordDto $dto): array
    {
        $user = $dto->getUser();
        $this->checkCredentials($user, $dto->getPassword());

        $this->checkNoTFA($user);

        return $this->oneTimeSecurityCodeService->generateRecoveryCodes();
    }

    public function removeTFA(TFAPasswordDto $dto): void
    {
        $user = $dto->getUser();
        $this->checkCredentials($user, $dto->getPassword());

        $this->checkNoTFA($user);

        $this->removeUserTFAData($user);
    }

    public function removeUsersTFA(User $user): void
    {
        $this->checkNoTFA($user);

        $this->removeUserTFAData($user);
    }

    public function register(RegisterDto $dto): User
    {
        $fields = $dto->toArray();
        $fields['password'] = Hash::make($dto->getPassword());
        $user = User::create($fields);

        $authenticated = Role::where('type', RoleType::AUTHENTICATED)->first();

        if ($authenticated) {
            $user->syncRoles($authenticated);
        }

        $this->consentService->syncUserConsents($user, $dto->getConsents());

        $preferences = UserPreference::create();
        $preferences->refresh();

        $user->preferences()->associate($preferences);

        if (!($dto->getMetadataPersonal() instanceof Missing)) {
            $this->metadataService->sync($user, $dto->getMetadataPersonal());
        }

        $user->save();

        $user->notify(new UserRegistered());

        UserCreated::dispatch($user);

        return $user;
    }

    public function updateProfile(UpdateProfileDto $dto): User
    {
        /** @var User $user */
        $user = Auth::user();
        $user->update($dto->toArray());

        $user->preferences()->update($dto->getPreferences()->toArray());

        $this->consentService->updateUserConsents(Collection::make($dto->getConsents()), $user);

        return $user;
    }

    private function verifyTFA(?string $code): void
    {
        if (!Auth::user()?->is_tfa_active && $code !== null) {
            $this->userLoginAttemptService->store();
            throw new ClientException(Exceptions::CLIENT_TFA_NOT_SET_UP, simpleLogs: true);
        }

        if (Auth::user()?->is_tfa_active) {
            match ($code) {
                null => $this->noTFACode(),
                default => $this->checkIsValidTFA($code),
            };
        }
    }

    private function noTFACode(): void
    {
        if (Auth::user()?->tfa_type === TFAType::EMAIL) {
            Auth::user()->securityCodes()->where('expires_at', '!=', null)->delete();
            $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode(
                Auth::user(),
                Config::get('tfa.code_expires_time')
            );

            TfaSecurityCodeEvent::dispatch(Auth::user(), $code);
            Auth::user()->notify(new TFASecurityCode($code));
        }
        throw new ClientException(
            Exceptions::CLIENT_TFA_REQUIRED,
            403,
            simpleLogs: true,
            errorArray: ['type' => Auth::user()?->tfa_type]
        );
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

    private function googleTFA(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $google_authenticator = new PHPGangsta_GoogleAuthenticator();

        $secret = $google_authenticator->createSecret();
        $qr_code_url = $google_authenticator->getQRCodeGoogleUrl(
            $user->email,
            $secret,
            Config::get('app.name')
        );

        $user->update([
            'tfa_type' => TFAType::APP,
            'tfa_secret' => $secret,
        ]);

        return [
            'type' => TFAType::APP,
            'secret' => $secret,
            'qr_code_url' => $qr_code_url,
        ];
    }

    private function emailTFA(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $user->securityCodes()->delete();
        $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode(
            $user,
            Config::get('tfa.code_expires_time')
        );

        $user->update([
            'tfa_type' => TFAType::EMAIL,
        ]);

        TfaInit::dispatch($user, $code);
        $user->notify(new TFAInitialization($code));

        return [
            'type' => TFAType::EMAIL,
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

    private function createTokens(string|bool $token, string $uuid): array
    {
        /** @var JWTSubject $user */
        $user = Auth::user();
        $identityToken = $this->tokenService->createToken(
            $user,
            new TokenType(TokenType::IDENTITY),
            $uuid,
        );
        $refreshToken = $this->tokenService->createToken(
            $user,
            new TokenType(TokenType::REFRESH),
            $uuid,
        );

        return [
            'token' => $token,
            'identity_token' => $identityToken,
            'refresh_token' => $refreshToken,
        ];
    }
}
