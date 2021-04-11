<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\AuthControllerSwagger;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\LoginHistoryResource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

class AuthController extends Controller implements AuthControllerSwagger
{
    public function login(LoginRequest $request)
    {
        if (!Auth::guard('web')->attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ])) {
            return Error::abort('Invalid credentials.', 400);
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
        if ($token = $request->user()->token()) {
            $token->update([
                'revoked' => true,
                'expires_at' => Carbon::now(),
            ]);
        }

        return response()->json(null, 204);
    }

    public function changePassword(PasswordChangeRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return Error::abort('Invalid credentials.', 400);
        }

        $user->update([
            'password' => Hash::make($request->input('password_new')),
        ]);

        return response()->json(null, 204);
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
}
