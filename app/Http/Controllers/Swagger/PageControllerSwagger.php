<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\PageOrderRequest;
use App\Http\Requests\PageStoreRequest;
use App\Http\Requests\PageUpdateRequest;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface PageControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/pages",
     *   summary="list page",
     *   tags={"Pages"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Page"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(): JsonResource;

    /**
     * @OA\Get(
     *   path="/pages/{slug}",
     *   summary="single page view",
     *   tags={"Pages"},
     *   @OA\Parameter(
     *     name="slug",
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
     *         ref="#/components/schemas/PageView"
     *       )
     *     )
     *   )
     * )
     */

    /**
     * @OA\Get(
     *   path="/pages/id:{id}",
     *   summary="alias",
     *   tags={"Pages"},
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
     *         ref="#/components/schemas/PageView"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function show(Page $page): JsonResource;

    /**
     * @OA\Post(
     *   path="/pages",
     *   summary="add new page",
     *   tags={"Pages"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/PageStore",
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/PageView",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(PageStoreRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/pages/id:{id}",
     *   summary="update page",
     *   tags={"Pages"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/PageUpdate",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/PageView",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Page $page, PageUpdateRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/pages/id:{id}",
     *   summary="delete page",
     *   tags={"Pages"},
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
    public function destroy(Page $page): JsonResponse;

    /**
     * @OA\Post(
     *   path="/pages/order",
     *   summary="change pages order",
     *   tags={"Pages"},
     *   @OA\Parameter(
     *     name="order",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/PageOrder",
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
    public function order(PageOrderRequest $request): JsonResponse;
}
