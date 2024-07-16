<?php

declare(strict_types=1);

namespace Domain\Organization\Controllers;

use App\Enums\SavedAddressType;
use App\Http\Controllers\Controller;
use Domain\Organization\Dtos\OrganizationSavedAddressCreateDto;
use Domain\Organization\Dtos\OrganizationSavedAddressUpdateDto;
use Domain\Organization\Models\Organization;
use Domain\Organization\Models\OrganizationSavedAddress;
use Domain\Organization\Resources\OrganizationSavedAddressResource;
use Domain\Organization\Services\OrganizationSavedAddressService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

final class OrganizationShippingAddressController extends Controller
{
    public function __construct(
        private readonly OrganizationSavedAddressService $organizationSavedAddressService,
    ) {}

    public function index(Organization $organization): JsonResource
    {
        return OrganizationSavedAddressResource::collection($this->organizationSavedAddressService->listAddresses($organization, SavedAddressType::SHIPPING));
    }

    public function store(Organization $organization, OrganizationSavedAddressCreateDto $dto): JsonResource
    {
        return OrganizationSavedAddressResource::make($this->organizationSavedAddressService->storeAddress($dto, SavedAddressType::SHIPPING, $organization->getKey()));
    }

    public function update(Organization $organization, OrganizationSavedAddress $delivery_address, OrganizationSavedAddressUpdateDto $dto): JsonResource
    {
        return OrganizationSavedAddressResource::make($this->organizationSavedAddressService->updateAddress($delivery_address, $dto, SavedAddressType::SHIPPING));
    }

    public function delete(Organization $organization, OrganizationSavedAddress $delivery_address): HttpResponse
    {
        $this->organizationSavedAddressService->delete($delivery_address);

        return Response::noContent();
    }

    public function indexMy(): JsonResource
    {
        return OrganizationSavedAddressResource::collection($this->organizationSavedAddressService->indexMy());
    }

    public function storeMy(OrganizationSavedAddressCreateDto $dto): JsonResource
    {
        return OrganizationSavedAddressResource::make($this->organizationSavedAddressService->storeAddressMy($dto));
    }

    public function updateMy(OrganizationSavedAddress $address, OrganizationSavedAddressUpdateDto $dto): JsonResource
    {
        return OrganizationSavedAddressResource::make($this->organizationSavedAddressService->updateAddressMy($address, $dto));
    }

    public function deleteMy(OrganizationSavedAddress $address): HttpResponse
    {
        $this->organizationSavedAddressService->deleteMy($address);

        return Response::noContent();
    }
}
