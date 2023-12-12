<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelfDeleteRequest;
use App\Http\Requests\UserIndexRequest;
use App\Http\Resources\ResourceCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Contracts\UserServiceContract;
use Domain\User\Dtos\UserCreateDto;
use Domain\User\Dtos\UserUpdateDto;
use Domain\User\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class UserController extends Controller
{
    public function __construct(
        private UserServiceContract $userService,
        private AuthService $authService,
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

    public function store(UserCreateDto $dto): JsonResource
    {
        $user = $this->userService->create($dto);

        return UserResource::make($user);
    }

    public function update(UserUpdateDto $dto, User $user): JsonResource
    {
        $resultUser = $this->userService->update(
            $user,
            $dto,
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
