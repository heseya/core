<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

interface UserControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/users",
     *   summary="user list",
     *   tags={"Users"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/User"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(UserIndexRequest $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/users/id:{id}",
     *   summary="user view",
     *   tags={"Users"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/User",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function show(User $user): JsonResource;

    /**
     * @OA\Post(
     *   path="/users",
     *   summary="add new user",
     *   tags={"Users"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/UserCreate",
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/User",
     *       )
     *     )
     *   )
     * )
     */
    public function store(UserCreateRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/users/id:{id}",
     *   summary="update user",
     *   tags={"Users"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="1c8705ce-5fae-4468-b88a-8784cb5414a0",
     *     ),
     *   ),
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/UserUpdate",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/User"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(User $user, UserUpdateRequest $request): JsonResource;

    /**
     * @OA\Delete(
     *   path="/users/id:{id}",
     *   summary="delete user",
     *   tags={"Users"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="1c8705ce-5fae-4468-b88a-8784cb5414a0",
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
    public function destroy(User $user): JsonResponse;
}
