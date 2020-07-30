<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\AuthControllerSwagger;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller implements AuthControllerSwagger
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        if (Auth::guard('web')->attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ])) {
            return Error::abort('Invalid credentials.', 400);
        }

        $user = Auth::guard('web')->user();
        $token = $user->createToken('Admin');

        return response()->json(['data' => [
            'token' => $token->accessToken,
            'expires_at' => $token->token->expires_at,
            'user' => UserResource::make($user),
            'scopes' => $token->token->scopes,
        ]], 200);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|max:255',
            'password_new' => 'required|string|max:255|min:10',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return Error::abort('Invalid credentials.', 400);
        }

        $hash = Hash::make($request->input('password_new'));

        $user->update([
            'password' => $hash,
        ]);

        // W przyszłości koniecznie zmienić na 204
        return response()->json(['data' => [
            'message' => 'OK',
        ]], 200);
    }
}
