<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\WebHookCreateRequest;
use App\Http\Requests\WebHookIndexRequest;
use App\Http\Requests\WebHookUpdateRequest;
use App\Models\WebHook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface WebHookControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/webhooks",
     *   summary="list webhooks by filters",
     *   tags={"WebHooks"},
     *   @OA\Parameter(
     *     name="name",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="Name search",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *     @OA\Parameter(
     *     name="url",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="URL search",
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
     *         @OA\Items(ref="#/components/schemas/WebHook"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(WebHookIndexRequest $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/webhooks/id:{id}",
     *   summary="show webhook",
     *   tags={"WebHooks"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="Name search",
     *     @OA\Schema(
     *       type="string",
     *       example="5b320ba6-d5ee-4870-bed2-1a101704c2c4"
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/WebHook",
     *       )
     *     )
     *   )
     * )
     */
    public function show(WebHook $webHook): JsonResource;

    /**
     * @OA\Post(
     *   path="/webhooks",
     *   summary="add new webhook",
     *   tags={"WebHooks"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/WebHookCreate",
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/WebHook",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(WebHookCreateRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/webhooks/id:{id}",
     *   summary="update webhook",
     *   tags={"WebHooks"},
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
     *     ref="#/components/requestBodies/WebHookUpdate",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Updated",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/WebHook",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(WebHook $webHook, WebHookUpdateRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/webhooks/id:{id}",
     *   summary="delete webhook",
     *   tags={"WebHooks"},
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
    public function destroy(WebHook $webHook): JsonResponse;
}
