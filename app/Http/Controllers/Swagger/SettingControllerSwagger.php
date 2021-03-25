<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\SettingCreateRequest;
use App\Http\Requests\SettingUpdateRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface SettingControllerSwagger
{
   /**
     * @OA\Get(
     *   path="/settings",
     *   summary="list settings",
     *   tags={"Settings"},
     *   @OA\Parameter(
     *     name="array",
     *     in="query",
     *     required=false,
     *     @OA\Schema(
     *       type="boolean",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Setting"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index(Request $request): JsonResponse;

    /**
     * @OA\Get(
     *   path="/settings/{name}",
     *   summary="view setting",
     *   tags={"Settings"},
     *   @OA\Parameter(
     *     name="name",
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
     *         ref="#/components/schemas/Setting"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function show(string $name): JsonResource;

    /**
     * @OA\Post(
     *   path="/settings",
     *   summary="add new setting",
     *   tags={"Settings"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Setting",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Setting",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(SettingCreateRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/settings/{name}",
     *   summary="update setting",
     *   tags={"Settings"},
     *   @OA\Parameter(
     *     name="name",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Setting",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Setting",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(string $name, SettingUpdateRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/settings/{name}",
     *   summary="delete setting",
     *   tags={"Settings"},
     *   @OA\Parameter(
     *     name="name",
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
    public function destroy(Setting $setting): JsonResponse;
}
