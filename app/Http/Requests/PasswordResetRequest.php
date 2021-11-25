<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="PasswordReset",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="email",
 *       type="string",
 *       example="admin@example.com",
 *     ),
 *   )
 * )
 */
class PasswordResetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
