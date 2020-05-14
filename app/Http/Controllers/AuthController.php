<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        if (Auth::guard('web')->attempt([
            'email' => $request->email,
            'password' => $request->password,
        ])) {

            $user = Auth::guard('web')->user();
            $token = $user->createToken('Admin')->accessToken;

            return response()->json(['data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ]], 200);
        } else {
            return Error::abort('Invalid credentials.', 400);
        }
    }

    
    /**
     * @OA\Patch(
     *   path="/user/password",
     *   summary="Change password",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="password",
     *         type="string",
     *         example="secret",
     *       ),
     *       @OA\Property(
     *         property="password_new",
     *         type="string",
     *         example="xsw@!QAZ34",
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
     *           property="messgae",
     *           type="string",
     *           example="OK",
     *         ),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|max:255',
            'password_new' => 'required|string|max:255|min:10',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return Error::abort('Invalid credentials.', 400);
        }

        $hash = Hash::make($request->password_new);

        $user->update([
            'password' => $hash,
        ]);

        return response()->json(['data' => [
            'message' => 'OK',
        ]], 200);
    }
}
