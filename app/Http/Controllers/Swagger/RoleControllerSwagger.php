<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface RoleControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/roles",
     *   summary="list roles",
     *   tags={"Roles"},
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
     *     name="description",
     *     in="query",
     *     description="Description search",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="assignable",
     *     in="query",
     *     description="Is the role assignable by current user",
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
     *         @OA\Items(ref="#/components/schemas/Role"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index(Request $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/roles/id:{id}",
     *   summary="view the role",
     *   tags={"Roles"},
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
     *         ref="#/components/schemas/RoleView"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function show(Role $role): JsonResource;

    /**
     * @OA\Post(
     *   path="/roles",
     *   summary="add a new role",
     *   tags={"Roles"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/RoleStore",
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/RoleView",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(RoleStoreRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/roles/id:{id}",
     *   summary="update the role",
     *   tags={"Roles"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/RoleUpdate",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/RoleView",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Role $role, RoleUpdateRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/roles/id:{id}",
     *   summary="delete the role",
     *   tags={"Roles"},
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
    public function destroy(Role $role): JsonResponse;
}
