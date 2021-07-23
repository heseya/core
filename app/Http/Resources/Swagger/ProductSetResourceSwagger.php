<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface ProductSetResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="ProductSet",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Name displayed to the user",
     *     example="AGD",
     *   ),
     *   @OA\Property(
     *     property="slug",
     *     type="string",
     *     description="Name used in the URL path",
     *     example="agd",
     *   ),
     *   @OA\Property(
     *     property="public",
     *     type="boolean",
     *     description="Whether set is visible to unauthorized users",
     *     example=true,
     *   ),
     *   @OA\Property(
     *     property="public_parent",
     *     type="boolean",
     *     description="When any parent node is set to private, this setting overrides set visibility",
     *     example=true,
     *   ),
     *   @OA\Property(
     *     property="hide_on_index",
     *     type="boolean",
     *     description="Whether set products should be hidden from the main page",
     *     example=false,
     *   ),
     *   @OA\Property(
     *     property="parent",
     *     type="object",
     *     ref="#/components/schemas/ProductSetNested",
     *   ),
     *   @OA\Property(
     *     property="children_ids",
     *     type="array",
     *     description="Ids of assigned subsets",
     *     @OA\Items(
     *       type="string",
     *       example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *     ),
     *   ),
     * )
     */

    /**
     * @OA\Schema(
     *   schema="ProductSetNested",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Name displayed to the user",
     *     example="AGD",
     *   ),
     *   @OA\Property(
     *     property="slug",
     *     type="string",
     *     description="Name used in the URL path",
     *     example="agd",
     *   ),
     *   @OA\Property(
     *     property="public",
     *     type="boolean",
     *     description="Whether set is visible to unauthorized users",
     *     example=true,
     *   ),
     *   @OA\Property(
     *     property="hide_on_index",
     *     type="boolean",
     *     description="Whether set products should be hidden from the main page",
     *     example=false,
     *   ),
     *   @OA\Property(
     *     property="parent_id",
     *     type="string",
     *     description="Id of set, this set belongs to",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="children_ids",
     *     type="array",
     *     description="Ids of assigned subsets",
     *     @OA\Items(
     *       type="string",
     *       example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *     ),
     *   ),
     * )
     */
    public function base(Request $request): array;
}
