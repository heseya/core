<?php

namespace App\Services;

use App\Dtos\TFAConfirmDto;
use App\Dtos\TFAPasswordDto;
use App\Dtos\TFASetupDto;
use App\Enums\RoleType;
use App\Enums\TFAType;
use App\Enums\TokenType;
use App\Exceptions\AuthException;
use App\Exceptions\TFAException;
use App\Models\App;
use App\Models\Role;
use App\Models\Token;
use App\Models\User;
use App\Notifications\ResetPassword;
use App\Notifications\TFAInitialization;
use App\Services\Contracts\AuthServiceContract;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use App\Services\Contracts\TokenServiceContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use PHPGangsta_GoogleAuthenticator;

class AuthService implements AuthServiceContract
{
    public function __construct(
        protected TokenServiceContract $tokenService,
        protected OneTimeSecurityCodeContract $oneTimeSecurityCodeService,
    ) {
    }

    public function login(string $email, string $password, ?string $ip, ?string $userAgent): array
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
            throw new AuthException('Invalid credentials', simpleLogs: true);
        }

        $identityToken = $this->tokenService->createToken(
            Auth::user(),
            new TokenType(TokenType::IDENTITY),
            $uuid,
        );
        $refreshToken = $this->tokenService->createToken(
            Auth::user(),
            new TokenType(TokenType::REFRESH),
            $uuid,
        );

        return [
            'token' => $token,
            'identity_token' => $identityToken,
            'refresh_token' => $refreshToken,
        ];
    }

    public function refresh(string $refreshToken, ?string $ip, ?string $userAgent): array
    {
        if (!$this->tokenService->validate($refreshToken)) {
            throw new AuthException('Invalid token');
        }

        $payload = $this->tokenService->payload($refreshToken);

        if (
            $payload->get('typ') !== TokenType::REFRESH ||
            Token::where('id', $payload->get('jti'))->where('invalidated', true)->exists()
        ) {
            throw new AuthException('Invalid token');
        }

        $uuid = Str::uuid()->toString();
        $user = $this->tokenService->getUser($refreshToken);
        $this->tokenService->invalidateToken($refreshToken);

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
        $this->tokenService->invalidateToken(Auth::getToken());
    }

    public function resetPassword(string $email): void
    {
        $user = User::whereEmail($email)->first();
        if ($user) {
            $token = Password::createToken($user);

            $user->notify(new ResetPassword($token));
        }
    }

    public function showResetPasswordForm(?string $email, ?string $token): User
    {
        if (!$token) {
            throw new AuthException('The token is invalid');
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
            throw new AuthException('Invalid identity token');
        }

        return $user;
    }

    public function unauthenticatedUser(): User
    {
        $user = User::make([
            'name' => 'Unauthenticated',
        ]);

        $roles = Role::where('type', RoleType::UNAUTHENTICATED)->get();
        $user->setRelation('roles', $roles);
        $user->id = null;

        return $user;
    }

    public function isUserAuthenticated(): bool
    {
        return Auth::user() instanceof User;
    }

    public function isAppAuthenticated(): bool
    {
        return Auth::user() instanceof App;
    }

    public function setupTFA(TFASetupDto $dto): array
    {
        if (!$this->isUserAuthenticated()) {
            throw new TFAException('Only users can set up Two-Factor Authentication');
        }

        if (Auth::user()->is_tfa_active) {
            throw new TFAException('Two-Factor Authentication is already setup.');
        }

        return match ($dto->getType()) {
            TFAType::APP => $this->googleTFA(),
            TFAType::EMAIL => $this->emailTFA(),
            default => throw new TFAException('Invalid Two-Factor Authentication type.'),
        };
    }

    public function confirmTFA(TFAConfirmDto $dto): array
    {
        if (!$this->isUserAuthenticated()) {
            throw new TFAException('Only users can set up Two-Factor Authentication');
        }

        if (Auth::user()->is_tfa_active) {
            throw new TFAException('Two-Factor Authentication is already setup.');
        }

        if (!Auth::user()->tfa_type) {
            throw new TFAException('First select Two-Factor Authentication type.');
        }

        $valid = match (Auth::user()->tfa_type) {
            TFAType::APP => $this->googleTFAVerify($dto->getCode()),
            TFAType::EMAIL => $this->emailTFAVerify($dto->getCode()),
        };

        if (!$valid) {
            throw new TFAException('Invalid Two-Factor Authentication token.');
        }

        Auth::user()->update([
            'is_tfa_active' => true,
        ]);

        return $this->oneTimeSecurityCodeService->generateRecoveryCodes();
    }

    public function generateRecoveryCodes(TFAPasswordDto $dto): array
    {
        $user = $dto->getUser();
        $this->checkCredentials($user, $dto->getPassword());

        if (!$user->is_tfa_active) {
            throw new TFAException('Two-Factor Authentication is not setup.');
        }

        return $this->oneTimeSecurityCodeService->generateRecoveryCodes();
    }

    public function showRecoveryCodes(TFAPasswordDto $dto): array
    {
        $this->checkCredentials($dto->getUser(), $dto->getPassword());

        return $this->oneTimeSecurityCodeService->showRecoveryCodes();
    }

    private function emailTFA(): array
    {
        Auth::user()->securityCodes()->delete();
        $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode(Auth::user(), 900000);

        Auth::user()->update([
            'tfa_type' => TFAType::EMAIL,
        ]);

        Auth::user()->notify(new TFAInitialization($code));

        return [
            'type' => TFAType::EMAIL,
        ];
    }

    private function emailTFAVerify(string $code): bool
    {
        $security_codes = Auth::user()->securityCodes()
            ->where('expires_at', '>', Carbon::now())
            ->orWhereNull('expires_at')->get();

        foreach ($security_codes as $security_code) {
            if ($security_code->code === $code) {
                return true;
            }
        }

        return false;
    }

    private function googleTFAVerify(string $code): bool
    {
        $google_authenticator = new PHPGangsta_GoogleAuthenticator();

        return $google_authenticator->verifyCode(Auth::user()->tfa_secret, $code);
    }

    private function googleTFA(): array
    {
        $google_authenticator = new PHPGangsta_GoogleAuthenticator();

        $secret = $google_authenticator->createSecret();
        $qr_code_url = $google_authenticator->getQRCodeGoogleUrl(Config::get('app.name'), $secret);

        Auth::user()->update([
            'tfa_type' => TFAType::APP,
            'tfa_secret' => $secret,
        ]);

        return [
            'type' => TFAType::APP,
            'secret' => $secret,
            'qr_code_url' => $qr_code_url,
        ];
    }

//    public function loginHistory(User $user): Builder
//    {
//        return Passport::token()
//            ->where('user_id', $user->getKey())
//            ->orderBy('created_at', 'DESC');
//    }
//
//    public function killActiveSession(User $user, string $oauthAccessTokensId)
//    {
//        $token = Passport::token()->where('id', $oauthAccessTokensId)->first();
//        if (!$token) {
//            throw new AuthException('User token does not exist');
//        }
//
//        if ($user->token() && $user->token()->getKey() === $token->id) {
//            throw new AuthException('Can\'t delete your current session');
//        }
//
//        $token->revoke();
//
//        return $this->loginHistory($user);
//    }
//
//    public function killAllSessions(User $user)
//    {
//        if (!$user->token()) {
//            throw new AuthException('User token does not exist');
//        }
//
//        $currentToken = $user->token()->getKey();
//        foreach ($user->tokens()->get() as $token) {
//            if ($currentToken === $token->getKey()) {
//                continue;
//            }
//
//            $token->revoke();
//        }
//
//        return Passport::token()
//            ->where('user_id', $user->getKey())
//            ->where('revoked', false)
//            ->get();
//    }

    private function getUserByEmail(string $email): User
    {
        $user = User::whereEmail($email)->first();
        if (!$user) {
            throw new AuthException('User does not exist', simpleLogs: true);
        }

        return $user;
    }

    private function checkCredentials(User $user, string $password): void
    {
        if (!Hash::check($password, $user->password)) {
            throw new AuthException('Invalid credentials', simpleLogs: true);
        }
    }

    private function checkPasswordResetToken(User $user, string $token): void
    {
        if (!Password::tokenExists($user, $token)) {
            throw new AuthException(
                'The token is invalid or inactive. Try to reset your password again.',
                simpleLogs: true
            );
        }
    }
}
