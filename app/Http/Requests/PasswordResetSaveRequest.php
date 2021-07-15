<?php

namespace App\Http\Requests;

/**
 * @OA\RequestBody(
 *   request="PasswordResetSave",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="token",
 *       type="string",
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
 *       property="password_new",
 *       type="string",
 *     ),
 *   )
 * )
 */
class PasswordResetSaveRequest extends PasswordChangeRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }
}
