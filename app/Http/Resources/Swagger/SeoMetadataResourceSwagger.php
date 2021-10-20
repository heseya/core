<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface SeoMetadataResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="Seo",
     *   @OA\Property(
     *     property="title",
     *     type="string",
     *     description="Displayed seo title",
     *     example="Title",
     *   ),
     *   @OA\Property(
     *     property="description",
     *     type="string",
     *     description="Displayed SEO description",
     *     example="Description SEO",
     *   ),
     *   @OA\Property(
     *     property="keywords",
     *     type="array",
     *     description="List of SEO keywords",
     *     @OA\Items(
     *         type="string",
     *         example="PHP",
     *     ),
     *   ),
     *   @OA\Property(
     *     property="og_image",
     *     type="object",
     *     ref="#/components/schemas/Media",
     *   ),
     *   @OA\Property(
     *     property="twitter_card",
     *     type="string",
     *     description="summary | summary_large_image",
     *     example="summary",
     *   ),
     * )
     */
    public function base(Request $request): array;
}
