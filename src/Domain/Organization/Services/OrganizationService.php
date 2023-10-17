<?php

declare(strict_types=1);

namespace Domain\Organization\Services;

use Domain\Organization\Dtos\OrganizationCreateDto;
use Domain\Organization\Dtos\OrganizationIndexDto;
use Domain\Organization\Dtos\OrganizationUpdateDto;
use Domain\Organization\Models\Organization;
use Domain\Organization\Repositories\OrganizationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class OrganizationService
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
    ) {}

    /**
     * @return LengthAwarePaginator<Organization>
     */
    public function index(OrganizationIndexDto $dto): LengthAwarePaginator
    {
        return $this->organizationRepository->index($dto);
    }

    public function show(string $id): Organization
    {
        return $this->organizationRepository->show($id);
    }

    public function create(OrganizationCreateDto $dto): Organization
    {
        return $this->organizationRepository->create($dto);
    }

    public function update(string $id, OrganizationUpdateDto $dto): Organization
    {
        return $this->organizationRepository->update($id, $dto);
    }

    public function delete(string $id): void
    {
        $this->organizationRepository->delete($id);
    }
}
