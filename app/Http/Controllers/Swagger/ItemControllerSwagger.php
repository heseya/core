<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\ItemCreateRequest;
use App\Http\Requests\ItemIndexRequest;
use App\Http\Requests\ItemUpdateRequest;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface ItemControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/items",
     *   summary="list items",
     *   tags={"Items"},
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
     *     name="sku",
     *     in="query",
     *     description="Sku search",
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
     *         @OA\Items(ref="#/components/schemas/Item"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index(ItemIndexRequest $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/items/id:{id}",
     *   summary="view item",
     *   tags={"Items"},
     *   @OA\Parameter(
     *     name="id",
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
     *         ref="#/components/schemas/Item"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function show(Item $item): JsonResource;

    /**
     * @OA\Post(
     *   path="/items",
     *   summary="add new item",
     *   tags={"Items"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Item",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Item",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(ItemCreateRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/items/id:{id}",
     *   summary="update item",
     *   tags={"Items"},
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
     *       ref="#/components/schemas/Item",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Item",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Item $item, ItemUpdateRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/items/id:{id}",
     *   summary="delete item",
     *   tags={"Items"},
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
    public function destroy(Item $item): JsonResponse;
}
