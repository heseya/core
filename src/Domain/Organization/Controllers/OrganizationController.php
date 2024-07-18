<?php

declare(strict_types=1);

namespace Domain\Organization\Controllers;

use App\Http\Controllers\Controller;
use Domain\Organization\Dtos\OrganizationCreateDto;
use Domain\Organization\Dtos\OrganizationIndexDto;
use Domain\Organization\Dtos\OrganizationUpdateDto;
use Domain\Organization\Models\Organization;
use Domain\Organization\Resources\OrganizationResource;
use Domain\Organization\Services\OrganizationService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

final class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {}

    public function index(OrganizationIndexDto $dto): JsonResource
    {
        return OrganizationResource::collection($this->organizationService->index($dto));
    }

    public function show(Organization $organization): JsonResource
    {
        return OrganizationResource::make($organization);
    }

    public function create(OrganizationCreateDto $dto): JsonResource
    {
        return OrganizationResource::make($this->organizationService->create($dto));
    }

    public function update(Organization $organization, OrganizationUpdateDto $dto): JsonResource
    {
        return OrganizationResource::make($this->organizationService->update($organization, $dto));
    }

    public function delete(string $id): HttpResponse
    {
        $this->organizationService->delete($id);

        return Response::noContent();
    }
}
