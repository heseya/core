<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\UserManagementControllerSwagger;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Contracts\UserManagementServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class UserManagementController extends Controller implements UserManagementControllerSwagger
{
    private UserManagementServiceContract $userManagementServiceContract;

    public function __construct(UserManagementServiceContract $userManagementServiceContract)
    {
        $this->userManagementServiceContract = $userManagementServiceContract;
    }

    public function index(Request $request): JsonResource
    {
        $users = $this->userManagementServiceContract->index();

        return UserResource::collection($users);
    }

    public function show(User $user): JsonResource
    {
        return UserResource::make($user);
    }

    public function store(UserCreateRequest $request): JsonResource
    {
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
