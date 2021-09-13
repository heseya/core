<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * @OA\RequestBody(
 *   request="ProductUpdate",
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
class ProductUpdateRequest extends ProductCreateRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['slug'] = [
            'required',
            'string',
            'max:255',
            'alpha_dash',
            Rule::unique('products')->ignore($this->route('product')->slug, 'slug'),
        ];

        return $rules;
    }
}
