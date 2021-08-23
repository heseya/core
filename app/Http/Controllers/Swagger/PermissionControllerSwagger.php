<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\PermissionIndexRequest;
use Illuminate\Http\Resources\Json\JsonResource;

interface PermissionControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/permissions",
     *   summary="list of permissions",
     *   tags={"Roles"},
     *   @OA\Parameter(
     *     name="assignable",
     *     in="query",
     *     description="Is the permission assignable by current user",
     *     @OA\Schema(
     *       type="boolean",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Permission"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index(PermissionIndexRequest $request): JsonResource;
}
