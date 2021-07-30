<?php

namespace App\Http\Requests;

/**
 * @OA\RequestBody(
 *   request="UserUpdate",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *       example="Marianna Szulc",
 *     ),
 *     @OA\Property(
 *       property="email",
 *       type="string",
 *       example="admin@example.com",
 *     ),
 *     @OA\Property(
 *       property="password",
 *       type="string",
 *     ),
 *     @OA\Property(
 *       property="remember_token",
 *       type="string",
 *     ),
 *   )
 * )
 */
class UserUpdateRequest extends UserCreateRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
