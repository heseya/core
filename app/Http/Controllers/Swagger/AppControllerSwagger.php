<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\AppDeleteRequest;
use App\Http\Requests\AppStoreRequest;
use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface AppControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/apps",
     *   summary="All registed app list",
     *   tags={"Apps"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   )
     * )
     */
    public function index(): JsonResource;

    /**
     * @OA\Post(
     *   path="/apps",
     *   summary="install a new app",
     *   tags={"Apps"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/AppStore",
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/AppView",
     *       )
     *     )
     *   ),
     * )
     */
    public function store(AppStoreRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/apps/id:{id}",
     *   summary="delete app",
     *   tags={"Apps"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="1c8705ce-5fae-4468-b88a-8784cb5414a0",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="force",
     *     in="query",
     *     allowEmptyValue=true,
     *     description="Force removal of the app",
     *     @OA\Schema(
     *       type="bool",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="Success",
     *   ),
     * )
     */
    public function destroy(App $app, AppDeleteRequest $request): JsonResponse;
}
