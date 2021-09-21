<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\AppStoreRequest;
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
}
