<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="IndexSchema",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *     ),
 *     @OA\Property(
 *       property="hidden",
 *       type="boolean",
 *       example="false",
 *     ),
 *     @OA\Property(
 *       property="required",
 *       type="boolean",
 *       example="true",
 *     ),
 *     @OA\Property(
 *       property="search",
 *       type="string",
 *     ),
 *     @OA\Property(
 *       property="sort",
 *       type="string",
 *     ),
 *   )
 * )
 */
class IndexSchemaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'hidden' => ['nullable', 'boolean'],
            'required' => ['nullable', 'boolean'],

            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:255'],
        ];
    }
}
