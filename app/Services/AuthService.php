<?php

namespace App\Services;

use App\Enums\TokenType;
use App\Exceptions\AuthException;
use App\Models\App;
use App\Models\Token;
use App\Models\User;
use App\Notifications\ResetPassword;
use App\Services\Contracts\AuthServiceContract;
use App\Services\Contracts\TokenServiceContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService implements AuthServiceContract
{
    public function __construct(protected TokenServiceContract $tokenService)
    {
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
            throw new AuthException('Invalid credentials');
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

    public function logout(): void
    {
        $this->tokenService->invalidateToken(Auth::getToken());
    }

    public function resetPassword(string $email): void
    {
        $user = $this->getUserByEmail($email);
        $token = Password::createToken($user);

        $user->notify(new ResetPassword($token));
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

    public function isUserAuthenticated(): bool
    {
        return Auth::user() instanceof User;
    }

    public function isAppAuthenticated(): bool
    {
        return Auth::user() instanceof App;
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
            throw new AuthException('User does not exist');
        }

        return $user;
    }

    private function checkCredentials(User $user, string $password): void
    {
        if (!Hash::check($password, $user->password)) {
            throw new AuthException('Invalid credentials');
        }
    }

    private function checkPasswordResetToken(User $user, string $token): void
    {
        if (!Password::tokenExists($user, $token)) {
            throw new AuthException('The token is invalid or inactive. Try to reset your password again.');
        }
    }
}
