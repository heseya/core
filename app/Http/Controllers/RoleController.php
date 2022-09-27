<?php

namespace App\Http\Controllers;

use App\Dtos\RoleCreateDto;
use App\Dtos\RoleSearchDto;
use App\Dtos\RoleUpdateDto;
use App\Http\Requests\RoleIndexRequest;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\Contracts\RoleServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class RoleController extends Controller
{
    public function __construct(
        private RoleServiceContract $roleService,
    ) {
    }

    public function index(RoleIndexRequest $request): JsonResource
    {
        $dto = RoleSearchDto::instantiateFromRequest($request);

        return RoleResource::collection(
            $this->roleService->search($dto),
        );
    }

    public function show(Role $role): JsonResource
    {
        $role->loadCount('users');

        return RoleResource::make($role);
    }

    public function store(RoleStoreRequest $request): JsonResource
    {
        $dto = RoleCreateDto::instantiateFromRequest($request);

        return RoleResource::make(
            $this->roleService->create($dto),
        );
    }

    public function update(Role $role, RoleUpdateRequest $request): JsonResource
    {
        $dto = RoleUpdateDto::instantiateFromRequest($request);

        return RoleResource::make(
            $this->roleService->update($role, $dto),
        );
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->roleService->delete($role);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
