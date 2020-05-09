<?php

namespace App\Http\Controllers;

use App\Error;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *   path="/login",
     *   summary="Login",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="email",
     *         type="string",
     *         example="admin@example.com",
     *       ),
     *       @OA\Property(
     *         property="password",
     *         type="string",
     *         example="secret",
     *       ),
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(
     *           property="token",
     *           type="string",
     *         ),
     *         @OA\Property(
     *           property="user",
     *           ref="#/components/schemas/User"
     *         )
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        if (Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ])) {

            $user = Auth::user();
            $token = $user->createToken('Admin')->accessToken;

            return response()->json(['data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ]], 200);
        } else {
            return Error::abort('Invalid credentials.', 400);
        }
    }
}
