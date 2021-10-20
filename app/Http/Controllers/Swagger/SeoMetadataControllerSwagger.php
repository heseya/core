<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\SeoMetadataRequest;
use App\Models\SeoMetadata;
use Illuminate\Http\Resources\Json\JsonResource;

interface SeoMetadataControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/seo",
     *   tags={"SEO"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Seo"
     *       )
     *     )
     *   ),
     * )
     */
    public function show(SeoMetadata $seoMetadata): JsonResource;

    /**
     * @OA\Patch(
     *   path="/seo",
     *   summary="Update or create global SEO",
     *   tags={"SEO"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Seo",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Updated",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Seo",
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Seo",
     *       )
     *     )
     *   ),
     * )
     */
    public function createOrUpdate(SeoMetadataRequest $request): JsonResource;
}
