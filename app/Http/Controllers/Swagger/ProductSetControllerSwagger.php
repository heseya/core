<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\ProductSetStoreRequest;
use App\Http\Requests\ProductSetUpdateRequest;
use App\Models\ProductSet;
use Illuminate\Http\Resources\Json\JsonResource;

interface ProductSetControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/product-sets",
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
    public function index(CategoryIndexRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/product-sets",
     *   tags={"Product Sets"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/ProductSet",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/ProductSet",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(ProductSetStoreRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/product-sets/id:{id}",
     *   tags={"Product Sets"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/ProductSet",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/ProductSet",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(ProductSet $set, ProductSetUpdateRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/product-sets/id:{id}",
     *   tags={"Product Sets"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
     *     )
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="Success",
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function destroy(ProductSet $category);
}
