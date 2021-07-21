<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="PageReorder",
 *   @OA\JsonContent(
 *     @OA\Property(
 *       property="pages",
 *       type="array",
 *       @OA\Items(),
 *     ),
 *   )
 * )
 */
class PageReorderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pages' => ['required', 'array'],
            'pages.*' => ['uuid', 'exists:pages,id'],
        ];
    }
}
