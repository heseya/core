<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordResetSaveRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\LoginHistoryResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\CustomResetPassword;
use App\Services\Contracts\AuthServiceContract;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpRespone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Response;
use Laravel\Passport\Passport;

class AuthService implements AuthServiceContract
{
    public function login(LoginRequest $request): JsonResource
    {
        if (!Auth::guard('web')->attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ])) {
            throw new AuthException('Invalid credentials');
        }

        $user = Auth::guard('web')->user();
        $token = $user->createToken('Admin');

        $token->token->update([
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return AuthResource::make($token);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->token();
        if ($token) {
            $token->update([
                'revoked' => true,
                'expires_at' => Carbon::now(),
            ]);
        }

        return Response::json(null, HttpRespone::HTTP_NO_CONTENT);
    }

    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $user = $this->getUserByEmail($request->input('email'));
        $token = Password::createToken($user);
        $user->notify(new CustomResetPassword($token));

        return response()->json(null, HttpRespone::HTTP_NO_CONTENT);
    }

    public function showResetPasswordForm(Request $request): JsonResource
    {
        if (!$request->input('token')) {
            throw new AuthException('The token is invalid');
        }

        $user = $this->getUserByEmail($request->input('email'));
        $this->checkPasswordResetToken($user, $request->input('token'));

        return UserResource::make($user);
    }

    public function saveResetPassword(PasswordResetSaveRequest $request): JsonResponse
    {
        $user = $this->getUserByEmail($request->input('email'));
        $this->checkPasswordResetToken($user, $request->input('token'));

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        Password::deleteToken($user);

        return response()->json(null, HttpRespone::HTTP_NO_CONTENT);
    }

    public function changePassword(PasswordChangeRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->checkCredentials($user, $request->input('password'));

        $user->update([
            'password' => Hash::make($request->input('password_new')),
        ]);

        return response()->json(null, HttpRespone::HTTP_NO_CONTENT);
    }

    public function loginHistory(Request $request): JsonResource
    {
        $tokens = Passport::token()
            ->where('user_id', $request->user()->getKey())
            ->orderBy('created_at', 'DESC');

        return LoginHistoryResource::collection(
            $tokens->paginate(12),
        );
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
