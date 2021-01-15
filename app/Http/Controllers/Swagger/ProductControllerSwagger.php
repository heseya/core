<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductShowRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     *     description="Full text search.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="brand",
     *     in="query",
     *     description="Brand slug.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="category",
     *     in="query",
     *     description="Category slug.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Sorting string.",
     *     @OA\Schema(
     *       type="string",
     *       example="price:asc,created_at:desc,name"
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
     *         ref="#/components/schemas/Product"
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
     *         ref="#/components/schemas/Product"
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
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="name",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="slug",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="price",
     *         type="number",
     *       ),
     *       @OA\Property(
     *         property="brand_id",
     *         type="string",
     *         example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
     *       ),
     *       @OA\Property(
     *         property="category_id",
     *         type="string",
     *         example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
     *       ),
     *       @OA\Property(
     *         property="description_md",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="public",
     *         type="boolean",
     *       ),
     *       @OA\Property(
     *         property="schemas",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(
     *             property="name",
     *             type="string",
     *           ),
     *           @OA\Property(
     *             property="type",
     *             type="integer",
     *           ),
     *           @OA\Property(
     *             property="required",
     *             type="boolean",
     *           ),
     *           @OA\Property(
     *             property="items",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(
     *                 property="item_id",
     *                 type="integer",
     *               ),
     *               @OA\Property(
     *                 property="extra_price",
     *                 type="number",
     *               )
     *             )
     *           )
     *         )
     *       ),
     *       @OA\Property(
     *         property="media",
     *         type="array",
     *         @OA\Items(
     *           type="string",
     *           example="0006c3a0-21af-4485-b7fe-9c42233cf03a",
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Product"
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
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="name",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="slug",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="price",
     *         type="number",
     *       ),
     *       @OA\Property(
     *         property="brand_id",
     *         type="integer",
     *       ),
     *       @OA\Property(
     *         property="category_id",
     *         type="integer",
     *       ),
     *       @OA\Property(
     *         property="description_md",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="digital",
     *         type="boolean",
     *       ),
     *       @OA\Property(
     *         property="public",
     *         type="boolean",
     *       ),
     *       @OA\Property(
     *         property="schemas",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(
     *             property="name",
     *             type="string",
     *           ),
     *           @OA\Property(
     *             property="type",
     *             type="integer",
     *           ),
     *           @OA\Property(
     *             property="required",
     *             type="boolean",
     *           ),
     *           @OA\Property(
     *             property="items",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(
     *                 property="item_id",
     *                 type="integer",
     *               ),
     *               @OA\Property(
     *                 property="extra_price",
     *                 type="number",
     *               )
     *             )
     *           )
     *         )
     *       ),
     *       @OA\Property(
     *         property="media",
     *         type="array",
     *         @OA\Items(
     *           type="integer",
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Product"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(ProductUpdateRequest $request, Product $product): JsonResponse;

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
