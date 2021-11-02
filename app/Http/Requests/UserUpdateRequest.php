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
 *     @OA\Property(
 *       property="roles",
 *       type="array",
 *       description="Ids of assigned roles",
 *       @OA\Items(
 *         type="string",
 *         example="026bc5f6-8373-4aeb-972e-e78d72a67121",
 *       ),
 *     ),
 *   )
 * )
 */
class UserUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')
                    ->ignoreModel($this->route('user'))
                    ->whereNull('deleted_at'),
            ],
            'roles' => ['array'],
            'roles.*' => ['uuid', 'exists:roles,id'],
        ];
    }
}
