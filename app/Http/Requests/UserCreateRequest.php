<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="UserCreate",
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
class UserCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'max:255', 'min:10'],
            'remember_token' => ['nullable', 'string', 'max:100'],
        ];
    }
}
