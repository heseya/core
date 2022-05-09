<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\ResourceCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Contracts\UserServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class UserController extends Controller
{
    private UserServiceContract $userService;

    public function __construct(UserServiceContract $userService)
    {
        $this->userService = $userService;
    }

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
            ),
            $request->input('sort', 'created_at:asc'),
            $request->input('limit', 12)
        );

        /** @var ResourceCollection $userCollection */
        $userCollection = UserResource::collection($paginator);

        return $userCollection->full($request->has('full'));
    }

    public function show(User $user): JsonResource
    {
        return UserResource::make($user);
    }

    public function store(UserCreateRequest $request): JsonResource
    {
        $user = $this->userService->create(
            $request->input('name'),
            $request->input('email'),
            $request->input('password'),
            $request->input('roles', []),
        );

        return UserResource::make($user);
    }

    public function update(User $user, UserUpdateRequest $request): JsonResource
    {
        $resultUser = $this->userService->update(
            $user,
            $request->input('name'),
            $request->input('email'),
            $request->input('roles'),
        );

        return UserResource::make($resultUser);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->destroy($user);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
