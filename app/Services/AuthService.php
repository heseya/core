<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\App;
use App\Models\User;
use App\Notifications\ResetPassword;
use App\Services\Contracts\AuthServiceContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService implements AuthServiceContract
{
    public function login(string $email, string $password, ?string $ip, ?string $userAgent)
    {
        $token = Auth::claims(['typ' => 'identity'])->attempt([
            'email' => $email,
            'password' => $password,
        ]);

        if ($token === null) {
            throw new AuthException('Invalid credentials');
        }

//        $user = Auth::guard('web')->user();
//        $token = $user->createToken('Admin');
//
//        $token->token->update([
//            'ip' => $ip,
//            'user_agent' => $userAgent,
//        ]);

//        $user = App::factory()->create();
//
//        $token = auth()->login($user);

        return $token;
    }

    public function logout(User $user): void
    {
        $token = $user->token();

        if ($token) {
            $token->update([
                'revoked' => true,
                'expires_at' => Carbon::now(),
            ]);
        }
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

    public function loginHistory(User $user): Builder
    {
        return Passport::token()
            ->where('user_id', $user->getKey())
            ->orderBy('created_at', 'DESC');
    }

    public function killActiveSession(User $user, string $oauthAccessTokensId)
    {
        $token = Passport::token()->where('id', $oauthAccessTokensId)->first();
        if (!$token) {
            throw new AuthException('User token does not exist');
        }

        if ($user->token() && $user->token()->getKey() === $token->id) {
            throw new AuthException('Can\'t delete your current session');
        }

        $token->revoke();

        return $this->loginHistory($user);
    }

    public function killAllSessions(User $user)
    {
        if (!$user->token()) {
            throw new AuthException('User token does not exist');
        }

        $currentToken = $user->token()->getKey();
        foreach ($user->tokens()->get() as $token) {
            if ($currentToken === $token->getKey()) {
                continue;
            }

            $token->revoke();
        }

        return Passport::token()
            ->where('user_id', $user->getKey())
            ->where('revoked', false)
            ->get();
    }

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
