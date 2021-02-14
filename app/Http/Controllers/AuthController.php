<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\AuthControllerSwagger;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Resources\AuthResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        return AuthResource::make($token);
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
}
