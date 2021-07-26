<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ProductSetShow",
 *   @OA\JsonContent(
 *     @OA\Parameter(
 *       name="tree",
 *       in="query",
 *       description="Return resource with recursively nested children instead of id's",
 *       @OA\Schema(
 *         type="bool",
 *       ),
 *     ),
 *   )
 * )
 */
class ProductSetShowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tree' => ['nullable', 'boolean'],
        ];
    }
}
