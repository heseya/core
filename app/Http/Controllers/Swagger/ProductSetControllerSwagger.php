<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\ProductSetAttachRequest;
use App\Http\Requests\ProductSetIndexRequest;
use App\Http\Requests\ProductSetProductsRequest;
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
     *     name="root",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="Return only root lists",
     *     @OA\Schema(
     *       type="bool",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="tree",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="Return sets starting from root with recursively nested children instead of id's",
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
     *             @OA\Schema(ref="#/components/schemas/ProductSetChildren"),
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
     *     description="Return set with recursively nested children instead of id's",
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
     *           @OA\Schema(ref="#/components/schemas/ProductSetParent"),
     *           @OA\Schema(ref="#/components/schemas/ProductSetParentChildren"),
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
     *     description="Return set with recursively nested children instead of id's",
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
     *           @OA\Schema(ref="#/components/schemas/ProductSetParent"),
     *           @OA\Schema(ref="#/components/schemas/ProductSetParentChildren"),
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
     *   @OA\Parameter(
     *     name="tree",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="Return set with recursively nested children instead of id's",
     *     @OA\Schema(
     *       type="bool",
     *     ),
     *   ),
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ProductSetStore",
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         oneOf={
     *           @OA\Schema(ref="#/components/schemas/ProductSetParent"),
     *           @OA\Schema(ref="#/components/schemas/ProductSetParentChildren"),
     *         }
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
     *   @OA\Parameter(
     *     name="tree",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="Return set with recursively nested children instead of id's",
     *     @OA\Schema(
     *       type="bool",
     *     ),
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
     *         type="object",
     *         oneOf={
     *           @OA\Schema(ref="#/components/schemas/ProductSetParent"),
     *           @OA\Schema(ref="#/components/schemas/ProductSetParentChildren"),
     *         }
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
     *   description="Delete set with all of it's subsets",
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
     *   path="/product-sets/reorder",
     *   tags={"Product Sets"},
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

    /**
     * @OA\Post(
     *   path="/product-sets/id:{id}/products",
     *   tags={"Product Sets"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="List of product id's",
     *     @OA\Schema(
     *       type="string",
     *       example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ProductSetAttach",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Product"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function attach(ProductSet $productSet, ProductSetAttachRequest $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/product-sets/id:{id}/products",
     *   tags={"Product Sets"},
     *   @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Pagination limit",
     *     @OA\Schema(
     *       type="number",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Product"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function products(ProductSet $productSet, ProductSetProductsRequest $request): JsonResource;
}
