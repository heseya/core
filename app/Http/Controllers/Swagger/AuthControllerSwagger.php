<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface AuthControllerSwagger
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
     *           property="expires_at",
     *           type="string",
     *         ),
     *         @OA\Property(
     *           property="user",
     *           ref="#/components/schemas/User"
     *         ),
     *         @OA\Property(
     *           property="scopes",
     *           type="array",
     *           items="",
     *         ),
     *       )
     *     )
     *   )
     * )
     */
    public function login(LoginRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/auth/logout",
     *   summary="Logout",
     *   tags={"Auth"},
     *   @OA\Response(
     *     response=204,
     *     description="Success no content",
     *   )
     * )
     */
    public function logout(Request $request): JsonResponse;

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
     *     response=204,
     *     description="Success",
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function changePassword(PasswordChangeRequest $request): JsonResponse;

    /**
     * @OA\Get(
     *   path="/auth/login-history",
     *   summary="Get login history",
     *   tags={"Auth"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function loginHistory(Request $request): JsonResource;
}
