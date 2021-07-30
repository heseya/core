<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\UserManagementControllerSwagger;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Contracts\UserManagementServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class UserManagementController extends Controller implements UserManagementControllerSwagger
{
    private UserManagementServiceContract $userManagementServiceContract;

    public function __construct(UserManagementServiceContract $userManagementServiceContract)
    {
        $this->userManagementServiceContract = $userManagementServiceContract;
    }

    public function index(UserIndexRequest $request): JsonResource
    {
        $paginator = $this->userManagementServiceContract->index(
            $request->only('search'),
            $request->input('sort'),
            $request->input('limit', 15)
        );

        return UserResource::collection($paginator);
    }

    public function show(User $user): JsonResource
    {
        return UserResource::make($user);
    }

    public function store(UserCreateRequest $request): JsonResource
    {
        $user = $this->userManagementServiceContract->create($request->validated());

        return UserResource::make($user);
    }

    public function update(User $user, UserUpdateRequest $request): JsonResource
    {
        $resultUser = $this->userManagementServiceContract->update($user, $request->validated());

        return UserResource::make($resultUser);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userManagementServiceContract->destroy($user);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
