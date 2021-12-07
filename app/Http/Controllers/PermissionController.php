<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionIndexRequest;
use App\Http\Resources\PermissionResource;
use App\Services\Contracts\PermissionServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionController extends Controller
{
    private PermissionServiceContract $permissionService;

    public function __construct(PermissionServiceContract $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index(PermissionIndexRequest $request): JsonResource
    {
        $assignable = $request->has('assignable') ? $request->boolean('assignable') : null;

        return PermissionResource::collection(
            $this->permissionService->getAll($assignable),
        );
    }
}
