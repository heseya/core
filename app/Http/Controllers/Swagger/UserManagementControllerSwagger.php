<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\UserCreateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface UserManagementControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/users/managements",
     *   summary="user list",
     *   tags={"User Management"},
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
    public function index(Request $request): JsonResource;

    /**
     * @OA\Get(
     *   path="/users/managements/id:{id}",
     *   summary="user view",
     *   tags={"User Management"},
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
     *   path="/users/managements",
     *   summary="add new user",
     *   tags={"User Management"},
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
}
