<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ProductStore",
 *   @OA\JsonContent(
 *     required={
 *       "name",
 *       "slug",
 *       "price",
 *       "public",
 *     },
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *     ),
 *     @OA\Property(
 *       property="slug",
 *       type="string",
 *     ),
 *     @OA\Property(
 *       property="price",
 *       type="number",
 *     ),
 *     @OA\Property(
 *       property="brand_id",
 *       type="string",
 *       example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
 *     ),
 *     @OA\Property(
 *       property="category_id",
 *       type="string",
 *       example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
 *     ),
 *     @OA\Property(
 *       property="description_html",
 *       type="string",
 *     ),
 *     @OA\Property(
 *       property="public",
 *       type="boolean",
 *     ),
 *     @OA\Property(
 *       property="media",
 *       type="array",
 *       @OA\Items(
 *         type="string",
 *         example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
 *       ),
 *     ),
 *     @OA\Property(
 *       property="tags",
 *       type="array",
 *       @OA\Items(
 *         type="string",
 *         example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
 *       ),
 *     ),
 *     @OA\Property(
 *       property="schemas",
 *       type="array",
 *       @OA\Items(
 *         type="string",
 *         example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
 *       ),
 *     ),
 *     @OA\Property(
 *       property="sets",
 *       type="array",
 *       @OA\Items(
 *         type="string",
 *         example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
 *       ),
 *     ),
 *   ),
 * )
 */
class ProductCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products', 'alpha_dash'],
            'price' => ['required', 'numeric', 'min:0'],
            'brand_id' => ['nullable', 'uuid', 'exists:product_sets,id'],
            'category_id' => ['nullable', 'uuid', 'exists:product_sets,id'],
            'description_html' => ['nullable', 'string'],
            'public' => ['required', 'boolean'],
            'quantity_step' => ['numeric'],

            'media' => ['nullable', 'array'],
            'media.*' => ['uuid', 'exists:media,id'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['uuid', 'exists:tags,id'],

            'schemas' => ['nullable', 'array'],
            'schemas.*' => ['uuid', 'exists:schemas,id'],

            'sets' => ['nullable', 'array'],
            'sets.*' => ['uuid', 'exists:product_sets,id'],
        ];
    }
}
