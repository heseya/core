<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\BrandIndexRequest;
use Illuminate\Http\Resources\Json\JsonResource;

interface BrandControllerSwagger
{
    /**
     * @OA\Get(
     *   deprecated=true,
     *   path="/brands",
     *   summary="list brands",
     *   tags={"Product Sets"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Brand"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(BrandIndexRequest $request): JsonResource;
}
