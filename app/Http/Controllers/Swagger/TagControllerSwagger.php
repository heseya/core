<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\TagCreateRequest;
use App\Http\Requests\TagIndexRequest;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface TagControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/tags",
     *   summary="list tags",
     *   tags={"Tags"},
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
     *     name="color",
     *     in="query",
     *     description="Color search",
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
     *         @OA\Items(ref="#/components/schemas/Tag"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index(TagIndexRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/tags",
     *   summary="add new tag",
     *   tags={"Tags"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Tag",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Tag",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(TagCreateRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/tags/id:{id}",
     *   summary="update tag",
     *   tags={"Tags"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="5b320ba6-d5ee-4870-bed2-1a101704c2c4",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Tag",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Tag",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Tag $tag, TagCreateRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/tags/id:{id}",
     *   summary="delete tag",
     *   tags={"Tags"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="5b320ba6-d5ee-4870-bed2-1a101704c2c4",
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
    public function destroy(Tag $tag): JsonResponse;
}
