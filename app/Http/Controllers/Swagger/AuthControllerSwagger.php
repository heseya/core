<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordResetSaveRequest;
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
     *           property="identity_token",
     *           type="string",
     *         ),
     *         @OA\Property(
     *           property="refresh_token",
     *           type="string",
     *         ),
     *         @OA\Property(
     *           property="user",
     *           ref="#/components/schemas/User"
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
//    public function logout(Request $request): JsonResponse;

    /**
     * @OA\Post(
     *   path="/users/reset-password",
     *   summary="Reset password",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/PasswordReset",
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
    public function resetPassword(PasswordResetRequest $request): JsonResponse;

    /**
     * @OA\Get(
     *   path="/users/reset-password/{token}/{email}",
     *   summary="Show reset password form",
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
    public function showResetPasswordForm(Request $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/users/save-reset-password",
     *   summary="save the reset password",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/PasswordResetSave",
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
    public function saveResetPassword(PasswordResetSaveRequest $request): JsonResponse;

    /**
     * @OA\Patch(
     *   path="/users/password",
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
     *   path="/auth/refresh",
     *   summary="Refresh access and identity tokens",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="refresh_token",
     *         type="string",
     *       ),
     *     ),
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
     *           property="identity_token",
     *           type="string",
     *         ),
     *         @OA\Property(
     *           property="refresh_token",
     *           type="string",
     *         ),
     *         @OA\Property(
     *           property="user",
     *           ref="#/components/schemas/User"
     *         ),
     *       )
     *     )
     *   )
     * )
     */
    public function refresh(Request $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/auth/login-history",
     *   summary="[DISABLED] Get login history",
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
//    public function loginHistory(Request $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/auth/kill-session/id:{id}",
     *   summary="[DISABLED] Allow to 'kill' active session",
     *   tags={"Auth"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="token id from the table 'oauth_access_tokens'",
     *     @OA\Schema(
     *       type="string",
     *       example="47e380b746d6cacb20473b82d911b2701a3c05422c65cbc4872045a100fcb72be554ad1680aef2bf",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
//    public function killActiveSession(Request $request, string $oauthAccessTokensId): JsonResource;

    /**
     * @OA\Get(
     *   path="/auth/kill-all-sessions",
     *   summary="[DISABLED] Allow to 'kill' all old sessions",
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
//    public function killAllSessions(Request $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/auth/profile",
     *   summary="get your own user resource",
     *   tags={"Auth"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/User",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function profile(Request $request): JsonResponse;
}
