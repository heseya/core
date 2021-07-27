<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Notifications\CustomResetPassword;
use App\Services\Contracts\AuthServiceContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\Passport;

class AuthService implements AuthServiceContract
{
    public function login(string $email, string $psswd, ?string $ip, ?string $userAgent)
    {
        if (!Auth::guard('web')->attempt([
            'email' => $email,
            'password' => $psswd,
        ])) {
            throw new AuthException('Invalid credentials');
        }

        $user = Auth::guard('web')->user();
        $token = $user->createToken('Admin');

        $token->token->update([
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);

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

        $user->notify(new CustomResetPassword($token));
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

    public function saveResetPassword(string $email, string $token, string $psswd): void
    {
        $user = $this->getUserByEmail($email);
        $this->checkPasswordResetToken($user, $token);

        $user->update([
            'password' => Hash::make($psswd),
        ]);

        Password::deleteToken($user);
    }

    public function changePassword(User $user, string $psswd, string $newPsswd): void
    {
        $this->checkCredentials($user, $psswd);

        $user->update([
            'password' => Hash::make($newPsswd),
        ]);
    }

    public function loginHistory(User $user)
    {
        return Passport::token()
            ->where('user_id', $user->getKey())
            ->orderBy('created_at', 'DESC');
    }

    public function killUserSession(User $user)
    {
        $token = $user->token();
        if ($token) {
            $result = $token->revoke();
            if (!$result) {
                throw new AuthException('User session invalidation error');
            }
        }

        return $this->loginHistory($user);
    }

    public function killAllOldUserSessions(User $user): void
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
