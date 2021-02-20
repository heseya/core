<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\IndexSchemaRequest;
use App\Http\Requests\SchemaStoreRequest;
use App\Models\Schema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface SchemaControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/schemas",
     *   summary="all schemas list",
     *   tags={"Schemas"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Schema"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index(IndexSchemaRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/schemas",
     *   summary="create schema",
     *   tags={"Schemas"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Schema"
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Schema"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(SchemaStoreRequest $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/schemas/id:{id}",
     *   tags={"Schemas"},
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
     *         ref="#/components/schemas/Schema"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function show(Schema $schema): JsonResource;

    /**
     * @OA\Patch(
     *   path="/schemas/id:{id}",
     *   summary="update schema",
     *   tags={"Schemas"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Schema"
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Schema"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(SchemaStoreRequest $request, Schema $schema): JsonResource;

    /**
     * @OA\Delete(
     *   path="/schemas/id:{id}",
     *   tags={"Schemas"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
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
    public function destroy(Schema $schema): JsonResponse;

    /**
     * @OA\Post(
     *   path="/schemas/id:{id}/attach/id:{product_id}",
     *   tags={"Schemas"},
     *   @OA\Parameter(
     *     name="id",
     *     description="Schema ID",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="product_id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
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
    public function attach(Schema $schema, string $product): JsonResponse;

    /**
     * @OA\Post(
     *   path="/schemas/id:{id}/detach/id:{product_id}",
     *   tags={"Schemas"},
     *   @OA\Parameter(
     *     name="id",
     *     description="Schema ID",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="product_id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
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
    public function detach(Schema $schema, string $product): JsonResponse;
}
