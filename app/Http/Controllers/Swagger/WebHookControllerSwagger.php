<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\WebHookIndexRequest;
use Illuminate\Http\Resources\Json\JsonResource;

interface WebHookControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/web-hooks",
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

//    public function store(Request $request): JsonResource;

//    public function update(Request $request, WebHook $webHook): JsonResource;

//    public function destroy(WebHook $webHook): JsonResponse;
}
