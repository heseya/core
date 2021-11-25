<?php

namespace App\Http\Requests;

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
 *       property="description_html",
 *       type="string",
 *     ),
 *     @OA\Property(
 *       property="description_short",
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
 *     @OA\Property(
 *       property="seo",
 *       type="object",
 *       ref="#/components/schemas/SeoStore",
 *     ),
 *   ),
 * )
 */
class ProductCreateRequest extends SeoMetadataRulesRequest
{
    public function rules(): array
    {
        return $this->rulesWithSeo([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products', 'alpha_dash'],
            'price' => ['required', 'numeric', 'min:0'],
            'description_html' => ['nullable', 'string'],
            'description_short' => ['nullable', 'string', 'between:30,5000'],
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
        ]);
    }
}
