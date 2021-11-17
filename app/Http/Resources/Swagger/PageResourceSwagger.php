<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface PageResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="Page",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="slug",
     *     type="string",
     *     description="Name used in the URL path",
     *     example="terms-and-conditions",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Name displayed as a page header",
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
     *   schema="PageView",
     *   allOf={
     *     @OA\Schema(ref="#/components/schemas/Page"),
     *   },
     *   @OA\Property(
     *     property="content_html",
     *     type="string",
     *     description="HTML formated text to be displayed as a main content under the page header",
     *     example="<h1>Lorem ipsum dolor sit amet</h1>",
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
