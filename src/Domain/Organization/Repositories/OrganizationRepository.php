<?php

declare(strict_types=1);

namespace Domain\Organization\Repositories;

use App\Models\Address;
use Domain\Organization\Dtos\OrganizationCreateDto;
use Domain\Organization\Dtos\OrganizationIndexDto;
use Domain\Organization\Dtos\OrganizationUpdateDto;
use Domain\Organization\Models\Organization;
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
        $address = Address::query()->firstOrCreate($dto->billing_address->toArray());

        $is_complete = $dto->sales_channel_id && !($dto->sales_channel_id instanceof Optional) && $dto->client_id && !($dto->client_id instanceof Optional);

        return Organization::query()->create(array_merge($dto->toArray(), [
            'billing_address_id' => $address->getKey(),
            'is_complete' => $is_complete,
        ]));
    }

    public function show(string $id): Organization
    {
        return Organization::query()->firstOrFail($id);
    }

    public function update(Organization $organization, OrganizationUpdateDto $dto): Organization
    {
        $organization->update($dto->toArray());
        $organization->increment('change_version');

        if ($organization->client_id && $organization->sales_channel_id) {
            $organization->update(['is_complete' => true]);
        } else {
            $organization->update(['is_complete' => false]);
        }

        return $organization;
    }

    public function delete(string $id): void
    {
        Organization::query()->where('id', '=', $id)->delete();
    }
}
