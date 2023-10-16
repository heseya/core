<?php

declare(strict_types=1);

namespace Domain\Organization\Repositories;

use App\Models\Address;
use Domain\Organization\Dtos\OrganizationCreateDto;
use Domain\Organization\Dtos\OrganizationIndexDto;
use Domain\Organization\Dtos\OrganizationUpdateDto;
use Domain\Organization\Enums\OrganizationStatus;
use Domain\Organization\Models\Organization;
use Domain\SalesChannel\SalesChannelRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelData\Optional;

final readonly class OrganizationRepository
{
    /**
     * @return LengthAwarePaginator<Organization>
     */
    public function index(OrganizationIndexDto $dto): LengthAwarePaginator
    {
        return Organization::searchByCriteria($dto->toArray())->paginate(Config::get('pagination.per_page'));
    }

    public function create(OrganizationCreateDto $dto): Organization
    {
        $salesChannelId = $dto->sales_channel_id instanceof Optional
            ? app(SalesChannelRepository::class)->getDefault()->getKey()
            : $dto->sales_channel_id;
        $address = Address::query()->firstOrCreate($dto->address);

        return Organization::query()->create(array_merge($dto->toArray(), [
            'address_id' => $address->getKey(),
            'status' => OrganizationStatus::UNVERIFIED->value,
            'sales_channel_id' => $salesChannelId,
        ]));
    }

    public function show(string $id): Organization
    {
        return Organization::query()->firstOrFail($id);
    }

    public function update(string $id, OrganizationUpdateDto $dto): Organization
    {
        $organization = Organization::query()->where('id', '=', $id)->firstOrFail();

        $organization->update($dto->toArray());

        return $organization;
    }

    public function delete(string $id): void
    {
        Organization::query()->where('id', '=', $id)->delete();
    }

    public function updateStatus(Organization $organization, OrganizationStatus $status): Organization
    {
        $organization->update(['status' => $status]);

        return $organization;
    }
}
