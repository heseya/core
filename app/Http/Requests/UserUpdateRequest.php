<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
 *   )
 * )
 */
class UserUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->where('email', $this->email)],
        ];
    }
}
