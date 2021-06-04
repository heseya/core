<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\CreateAppRequest;
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
     *   summary="Register new app",
     *   tags={"Apps"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="url",
     *         type="string",
     *         example="https://test.app.heseya.com",
     *       ),
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   )
     * )
     */
    public function store(CreateAppRequest $request): JsonResponse;
}
