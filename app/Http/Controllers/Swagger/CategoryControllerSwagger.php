<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\ProductSetIndexRequest;
use Illuminate\Http\Resources\Json\JsonResource;

interface CategoryControllerSwagger
{
    /**
     * @OA\Get(
     *   deprecated=true,
     *   path="/categories",
     *   summary="list categories",
     *   tags={"Product Sets"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/ProductSet"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(ProductSetIndexRequest $request): JsonResource;
}
