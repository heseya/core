<?php

declare(strict_types=1);

namespace Domain\Organization\Services;

use App\Enums\SavedAddressType;
use Domain\Organization\Dtos\OrganizationCreateDto;
use Domain\Organization\Dtos\OrganizationIndexDto;
use Domain\Organization\Dtos\OrganizationSavedAddressCreateDto;
use Domain\Organization\Dtos\OrganizationUpdateDto;
use Domain\Organization\Models\Organization;
use Domain\Organization\Repositories\OrganizationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class OrganizationService
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private OrganizationSavedAddressService $organizationSavedAddressService,
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
        $organization = $this->organizationRepository->create($dto);

        $forceDefault = count($dto->shipping_addresses) === 1;
        /** @var OrganizationSavedAddressCreateDto $shipping_address */
        foreach ($dto->shipping_addresses as $shipping_address) {
            $this->organizationSavedAddressService->storeAddress($shipping_address, SavedAddressType::SHIPPING, $organization->getKey(), $forceDefault);
        }
        $organization->refresh();

        return $organization;
    }

    public function update(Organization $organization, OrganizationUpdateDto $dto): Organization
    {
        return $this->organizationRepository->update($organization, $dto);
    }

    public function delete(string $id): void
    {
        $this->organizationRepository->delete($id);
    }
}
