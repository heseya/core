<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\ProductSetIndexRequest;
use App\Http\Requests\ProductSetReorderRequest;
use App\Http\Requests\ProductSetShowRequest;
use App\Http\Requests\ProductSetStoreRequest;
use App\Http\Requests\ProductSetUpdateRequest;
use App\Models\ProductSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface ProductSetControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/product-sets",
     *   tags={"Product Sets"},
     *   @OA\Parameter(
     *     name="tree",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="Return resource with recursively nested children instead of id's",
     *     @OA\Schema(
     *       type="bool",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Full text search",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="name",
     *     in="query",
     *     description="Name search",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="slug",
     *     in="query",
     *     description="Slug search",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="public",
     *     in="query",
     *     description="Is public search",
     *     @OA\Schema(
     *       type="bool",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           oneOf={
     *             @OA\Schema(ref="#/components/schemas/ProductSet"),
     *             @OA\Schema(ref="#/components/schemas/ProductSetTree"),
     *           },
     *         )
     *       )
     *     )
     *   ),
     * )
     */
    public function index(ProductSetIndexRequest $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/product-sets/{slug}",
     *   tags={"Product Sets"},
     *   @OA\Parameter(
     *     name="slug",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="tree",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="Return resource with recursively nested children instead of id's",
     *     @OA\Schema(
     *       type="bool",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         oneOf={
     *           @OA\Schema(ref="#/components/schemas/ProductSet"),
     *           @OA\Schema(ref="#/components/schemas/ProductSetTree"),
     *         }
     *       )
     *     )
     *   ),
     * )
     */

    /**
     * @OA\Get(
     *   path="/product-sets/id:{id}",
     *   tags={"Product Sets"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="tree",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="Return resource with recursively nested children instead of id's",
     *     @OA\Schema(
     *       type="bool",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         oneOf={
     *           @OA\Schema(ref="#/components/schemas/ProductSet"),
     *           @OA\Schema(ref="#/components/schemas/ProductSetTree"),
     *         }
     *       )
     *     )
     *   ),
     * )
     */
    public function show(ProductSet $set, ProductSetShowRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/product-sets",
     *   tags={"Product Sets"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ProductSetStore",
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
     *     ref="#/components/requestBodies/ProductSetUpdate",
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
    public function destroy(ProductSet $category): JsonResponse;

    /**
     * @OA\Post(
     *   path="/product-sets/reorder/id:{id}",
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
     *     ref="#/components/requestBodies/ProductSetReorder",
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
    public function reorder(ProductSet $productSet, ProductSetReorderRequest $request): JsonResponse;
}
