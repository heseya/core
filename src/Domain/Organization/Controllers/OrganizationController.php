<?php

declare(strict_types=1);

namespace Domain\Organization\Controllers;

use App\Exceptions\ClientException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Domain\Organization\Dtos\OrganizationCreateDto;
use Domain\Organization\Dtos\OrganizationIndexDto;
use Domain\Organization\Dtos\OrganizationPublicUpdateDto;
use Domain\Organization\Dtos\OrganizationRegisterDto;
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

    public function delete(Organization $organization): HttpResponse
    {
        $this->organizationService->delete($organization);

        return Response::noContent();
    }

    public function register(OrganizationRegisterDto $dto): JsonResource
    {
        return OrganizationResource::make($this->organizationService->register($dto));
    }

    /**
     * @throws ClientException
     */
    public function myOrganization(): JsonResource
    {
        return OrganizationResource::make($this->organizationService->myOrganization());
    }

    /**
     * @throws ClientException
     */
    public function myOrganizationEdit(OrganizationPublicUpdateDto $dto): JsonResource
    {
        return OrganizationResource::make($this->organizationService->myOrganizationEdit($dto));
    }

    public function organizationUsers(Organization $organization): JsonResource
    {
        return UserResource::collection($this->organizationService->organizationUsers($organization));
    }
}
