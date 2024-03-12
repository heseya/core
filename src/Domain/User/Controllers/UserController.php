<?php

declare(strict_types=1);

namespace Domain\User\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResourceCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Domain\User\Dtos\UserCreateDto;
use Domain\User\Dtos\UserIndexDto;
use Domain\User\Dtos\UserSoftDeleteDto;
use Domain\User\Dtos\UserUpdateDto;
use Domain\User\Services\AuthService;
use Domain\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AuthService $authService,
    ) {}

    public function index(UserIndexDto $dto): JsonResource
    {
        $paginator = $this->userService->index(
            $dto->only(
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
            $dto->sort,
        );

        /** @var ResourceCollection $userCollection */
        $userCollection = UserResource::collection($paginator);

        return $userCollection->full($dto->full);
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

    public function selfRemove(UserSoftDeleteDto $dto): JsonResponse
    {
        $this->authService->selfRemove($dto->password);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
