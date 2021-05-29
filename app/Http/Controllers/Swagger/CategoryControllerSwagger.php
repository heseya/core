<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use Illuminate\Http\Resources\Json\JsonResource;

interface CategoryControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/categories",
     *   summary="list categories",
     *   tags={"Categories"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Category"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(CategoryIndexRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/categories",
     *   summary="add new category",
     *   tags={"Categories"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Category",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Category",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(CategoryCreateRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/categories/id:{id}",
     *   summary="update category",
     *   tags={"Categories"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Category",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Category",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Category $category, CategoryUpdateRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/categories/id:{id}",
     *   summary="delete category",
     *   tags={"Categories"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
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
    public function destroy(Category $category);
}
