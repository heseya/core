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
     *     example="Terms & Conditions",
     *   ),
     *   @OA\Property(
     *     property="public",
     *     type="boolean",
     *     example=true,
     *     description="Whether page is visible to unauthorized users",
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
     *     property="content_html",
     *     type="string",
     *     description="HTML formated text to be displayed as a main content under the page header",
     *     example="<h1>Lorem ipsum dolor sit amet</h1>",
     *   ),
     *   @OA\Property(
     *     property="content_md",
     *     type="string",
     *     example="# Lorem ipsum dolor sit amet",
     *     description="MD formated content for compatibility reasons",
     *   ),
     *   @OA\Property(
     *     property="meta_description",
     *     type="string",
     *     example="Lorem ipsum dolor sit amet",
     *     description="Tag stripped and trimmed version of HTML content to use as meta data",
     *   ),
     * )
     */
    public function view(Request $request): array;
}
