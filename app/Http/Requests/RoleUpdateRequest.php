<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="RoleUpdate",
 *   @OA\JsonContent(
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
class RoleUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string'],
            'description' => ['string'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
