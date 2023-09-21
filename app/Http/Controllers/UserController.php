<?php

namespace App\Http\Controllers;

use App\Dtos\UserCreateDto;
use App\Dtos\UserDto;
use App\Http\Requests\SelfDeleteRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\ResourceCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Contracts\AuthServiceContract;
use App\Services\Contracts\UserServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class UserController extends Controller
{
    public function __construct(
        private UserServiceContract $userService,
        private AuthServiceContract $authService,
    ) {}

    public function index(UserIndexRequest $request): JsonResource
    {
        $paginator = $this->userService->index(
            $request->only(
                'name',
                'email',
                'search',
                'ids',
                'metadata',
                'metadata_private',
                'consent_name',
                'consent_id',
                'roles',
            ),
            $request->input('sort', 'created_at:asc'),
        );

        /** @var ResourceCollection $userCollection */
        $userCollection = UserResource::collection($paginator);

        return $userCollection->full($request->boolean('full'));
    }

    public function show(User $user): JsonResource
    {
        return UserResource::make($user);
    }

    public function store(UserCreateRequest $request): JsonResource
    {
        $user = $this->userService->create(
            UserCreateDto::instantiateFromRequest($request),
        );

        return UserResource::make($user);
    }

    public function update(User $user, UserUpdateRequest $request): JsonResource
    {
        $resultUser = $this->userService->update(
            $user,
            UserDto::instantiateFromRequest($request),
        );

        return UserResource::make($resultUser);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->destroy($user);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function selfRemove(SelfDeleteRequest $request): JsonResponse
    {
        $this->authService->selfRemove($request->input('password'));

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
