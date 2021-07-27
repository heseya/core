<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
 *   )
 * )
 */
class PasswordResetSaveRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:255', 'min:10'],
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }
}