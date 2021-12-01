<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductShowRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface ProductControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/products",
     *   summary="list products",
     *   tags={"Products"},
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
     *       type="boolean",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="sets[]",
     *     in="query",
     *     description="Product set array slug search",
     *     example="sets[]=category-teapots&sets[]=category-mugs",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Sorting string",
     *     @OA\Schema(
     *       type="string",
     *       example="price:asc,created_at:desc,name"
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="tags[]",
     *     in="query",
     *     description="Tag array id search",
     *     example="tags[]=5a61f3a1-1cd1-4e71-bf7d-0d3a159bd6b0&tags[]=33e37b2a-44e9-4d35-88db-d9a79a61e557",
     *     @OA\Schema(
     *       type="string",
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
     *   )
     * )
     */
    public function index(ProductIndexRequest $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/products/{slug}",
     *   summary="single product view",
     *   tags={"Products"},
     *   @OA\Parameter(
     *     name="slug",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/ProductView"
     *       )
     *     )
     *   )
     * )
     */

    /**
     * @OA\Get(
     *   path="/products/id:{id}",
     *   summary="alias",
     *   tags={"Products"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/ProductView"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function show(ProductShowRequest $request, Product $product): JsonResource;

    /**
     * @OA\Post(
     *   path="/products",
     *   summary="create product",
     *   tags={"Products"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ProductStore",
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/ProductView"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(ProductCreateRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/products/id:{id}",
     *   summary="update product",
     *   tags={"Products"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/ProductUpdate",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/ProductView"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(ProductUpdateRequest $request, Product $product): JsonResource;

    /**
     * @OA\Delete(
     *   path="/products/id:{id}",
     *   summary="delete product",
     *   tags={"Products"},
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
    public function destroy(Product $product): JsonResponse;
}
