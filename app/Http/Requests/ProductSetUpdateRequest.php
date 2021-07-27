<?php

namespace App\Http\Requests;

use App\Rules\ProhibitedWith;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\RequestBody(
 *   request="ProductSetUpdate",
 *   @OA\JsonContent(
 *     required={
 *       "name",
 *       "slug",
 *     },
 *     @OA\Property(
 *       property="name",
 *       type="string",
 *       description="Name displayed to the user",
 *       example="AGD",
 *     ),
 *     @OA\Property(
 *       property="slug",
 *       type="string",
 *       description="Name used in the URL path",
 *       example="agd",
 *     ),
 *     @OA\Property(
 *       property="public",
 *       type="boolean",
 *       description="Whether set is visible to unauthorized users",
 *       example=true,
 *     ),
 *     @OA\Property(
 *       property="hide_on_index",
 *       type="boolean",
 *       description="Whether set products should be hidden from the main page",
 *       example=false,
 *     ),
 *     @OA\Property(
 *       property="parent_id",
 *       type="string",
 *       description="Id of set, this set belongs to",
 *       example="026bc5f6-8373-4aeb-972e-e78d72a67121",
 *     ),
 *     @OA\Property(
 *       property="children_ids",
 *       type="array",
 *       description="Ids of assigned subsets",
 *       @OA\Items(
 *         type="string",
 *         example="026bc5f6-8373-4aeb-972e-e78d72a67121",
 *       ),
 *     ),
 *   )
 * )
 */
class ProductSetUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required_without:slug', 'string', 'max:255', 'alpha_dash'],
            'override_slug' => [
                new ProhibitedWith('child_slug'),
                'string',
                'max:255',
                'unique:product_sets',
                'alpha_dash',
            ],
            'public' => ['required', 'boolean'],
            'hide_on_index' => ['required', 'boolean'],
            'parent_id' => ['present', 'nullable', 'uuid', 'exists:product_sets,id'],
            'children_ids' => ['present', 'array'],
            'children_ids.*' => ['uuid', 'exists:product_sets,id'],
        ];
    }
}
