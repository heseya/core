<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface ProductResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="Product",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="slug",
     *     type="string",
     *     description="Name used in the URL path",
     *     example="white-socks",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Displayed product name",
     *     example="White socks",
     *   ),
     *   @OA\Property(
     *     property="price",
     *     type="numeric",
     *     description="Base price of the product",
     *     example=10.99,
     *   ),
     *   @OA\Property(
     *     property="public",
     *     type="boolean",
     *     example=true,
     *     description="Whether product is not set to be hidden.",
     *   ),
     *   @OA\Property(
     *     property="visible",
     *     type="boolean",
     *     example=true,
     *     description="Whether product is visible due to public setting and set visibility",
     *   ),
     *   @OA\Property(
     *     property="available",
     *     type="boolean",
     *     example=true,
     *     description="Whether all parts for minimum product config are availble",
     *   ),
     *   @OA\Property(
     *     property="quantity_step",
     *     type="numeric",
     *     description="In what increments can you increase the product quantity",
     *     example=0.05,
     *   ),
     *   @OA\Property(
     *     property="cover",
     *     type="object",
     *     ref="#/components/schemas/Media",
     *   ),
     *   @OA\Property(
     *     property="tags",
     *     type="array",
     *     description="Ids of assigned subsets",
     *     @OA\Items(
     *       type="object",
     *       ref="#/components/schemas/Tag",
     *     ),
     *   ),
     * )
     */
    public function base(Request $request): array;

    /**
     * @OA\Schema(
     *   schema="ProductView",
     *   allOf={
     *     @OA\Schema(ref="#/components/schemas/Product"),
     *   },
     *   @OA\Property(
     *     property="user_id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="original_id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="description_html",
     *     type="string",
     *     description="HTML formated text to be displayed as a product description",
     *     example="<h1>Lorem ipsum dolor sit amet</h1>",
     *   ),
     *   @OA\Property(
     *     property="description_md",
     *     type="string",
     *     example="# Lorem ipsum dolor sit amet",
     *     description="MD formated content for compatibility reasons",
     *   ),
     *   @OA\Property(
     *     property="meta_description",
     *     type="string",
     *     example="Lorem ipsum dolor sit amet",
     *     description="Tag stripped and trimmed version of HTML description to use as meta data",
     *   ),
     *   @OA\Property(
     *     property="gallery",
     *     type="array",
     *     description="Ids of assigned subsets",
     *     @OA\Items(
     *       type="object",
     *       ref="#/components/schemas/Media",
     *     ),
     *   ),
     *   @OA\Property(
     *     property="schemas",
     *     type="array",
     *     description="Ids of assigned subsets",
     *     @OA\Items(
     *       type="object",
     *       ref="#/components/schemas/Schema",
     *     ),
     *   ),
     *   @OA\Property(
     *     property="sets",
     *     type="array",
     *     description="Ids of assigned subsets",
     *     @OA\Items(
     *       type="object",
     *       ref="#/components/schemas/ProductSet",
     *     ),
     *   ),
     * )
     */
    public function view(Request $request): array;
}
