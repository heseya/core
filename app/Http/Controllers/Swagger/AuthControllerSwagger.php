<?php

namespace App\Http\Controllers\Swagger;

use Illuminate\Http\Request;

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
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function login(Request $request);


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
    public function changePassword(Request $request);
}
