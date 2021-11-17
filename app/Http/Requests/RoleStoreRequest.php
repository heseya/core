<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="RoleStore",
 *   @OA\JsonContent(
 *     required={
 *       "name",
 *     },
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *       description="Name of the role",
 *       example="Admin",
 *     ),
 *     @OA\Property(
 *       property="description",
 *       type="string",
 *       description="Description of the role",
 *       example="Role with permission to everything",
 *     ),
 *     @OA\Property(
 *       property="permissions",
 *       type="array",
 *       description="Permission names",
 *       @OA\Items(
 *         type="string",
 *         example="roles.add",
 *       ),
 *     ),
 *   )
 * )
 */
class RoleStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
