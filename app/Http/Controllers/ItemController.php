<?php

namespace App\Http\Controllers;

use App\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ItemResource;
use App\Http\Resources\ItemShortResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ItemController extends Controller
{
    /**
     * @OA\Get(
     *   path="/items",
     *   summary="list items",
     *   tags={"Items"},
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
    public function index(): ResourceCollection
    {
        return ItemShortResource::collection(
            Item::paginate(12),
        );
    }

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
     *       type="integer",
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
    public function view(Item $item): JsonResource
    {
        return new ItemResource($item);
    }

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
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'string|unique:items|max:255|nullable'
        ]);

        $item = Item::create($request->all());

        return (new ItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Put(
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
    public function update(Item $item, Request $request): JsonResource
    {
        $request->validate([
            'name' => 'string|max:255',
            'sku' => [
                'string',
                'max:255',
                Rule::unique('items')->ignore($item->sku, 'sku'),
            ]
        ]);

        $item->update($request->all());

        return new ItemResource($item);
    }

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
    public function delete(Item $item): JsonResponse
    {
        $item->delete();

        return response()->json(null, 204);
    }
}