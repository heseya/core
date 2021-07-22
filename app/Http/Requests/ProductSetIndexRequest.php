<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ProductSetIndex",
 *   @OA\JsonContent(
 *     @OA\Parameter(
 *       name="tree",
 *       in="query",
 *       description="Return resource with recursively nested children instead of id's",
 *       @OA\Schema(
 *         type="bool",
 *       ),
 *     ),
 *     @OA\Parameter(
 *       name="search",
 *       in="query",
 *       description="Full text search",
 *       @OA\Schema(
 *         type="string",
 *       ),
 *     ),
 *     @OA\Parameter(
 *       name="name",
 *       in="query",
 *       description="Name search",
 *       @OA\Schema(
 *         type="string",
 *       ),
 *     ),
 *     @OA\Parameter(
 *       name="slug",
 *       in="query",
 *       description="Slug search",
 *       @OA\Schema(
 *         type="string",
 *       ),
 *     ),
 *     @OA\Parameter(
 *       name="public",
 *       in="query",
 *       description="Is public search",
 *       @OA\Schema(
 *         type="bool",
 *       ),
 *     ),
 *   )
 * )
 */
class ProductSetIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'public' => ['nullable', 'boolean'],
            'tree' => ['nullable', 'boolean'],
        ];
    }
}
