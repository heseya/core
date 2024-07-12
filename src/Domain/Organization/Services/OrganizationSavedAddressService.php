<?php

declare(strict_types=1);

namespace Domain\Organization\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SavedAddressType;
use App\Exceptions\ClientException;
use App\Models\Address;
use Domain\Organization\Dtos\OrganizationSavedAddressCreateDto;
use Domain\Organization\Dtos\OrganizationSavedAddressUpdateDto;
use Domain\Organization\Models\Organization;
use Domain\Organization\Models\OrganizationSavedAddress;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

final class OrganizationSavedAddressService
{
    public function storeAddress(OrganizationSavedAddressCreateDto $dto, SavedAddressType $type, string $organization_id, bool $force_default = false): OrganizationSavedAddress
    {
        $savedAddress = DB::transaction(function () use ($dto, $type, $organization_id, $force_default): OrganizationSavedAddress {
            $address = Address::create($dto->address->toArray());

            return OrganizationSavedAddress::create([
                'name' => $dto->name,
                'default' => $force_default ?: $dto->default,
                'organization_id' => $organization_id,
                'address_id' => $address->getKey(),
                'type' => $type,
            ]);
        });

        if ($savedAddress->default) {
            $this->defaultSet($savedAddress, $type);
        }

        return $savedAddress;
    }

    public function defaultSet(OrganizationSavedAddress $address, SavedAddressType $type): void
    {
        OrganizationSavedAddress::where('id', '!=', $address->getKey())
            ->where('organization_id', '=', $address->organization_id)
            ->where('type', '=', $type->value)
            ->update(['default' => false]);
    }

    /**
     * @return LengthAwarePaginator<OrganizationSavedAddress>
     */
    public function listAddresses(Organization $organization, SavedAddressType $type): LengthAwarePaginator
    {
        return OrganizationSavedAddress::query()
            ->where('type', '=', $type)
            ->where('organization_id', $organization->getKey())
            ->paginate(Config::get('pagination.per_page'));
    }

    public function delete(OrganizationSavedAddress $address): void
    {
        if ($address->default) {
            throw new ClientException(Exceptions::CLIENT_ORGANIZATION_ADDRESS_REMOVE_DEFAULT);
        }

        $address->delete();
    }

    public function updateAddress(OrganizationSavedAddress $address, OrganizationSavedAddressUpdateDto $dto, SavedAddressType $type): OrganizationSavedAddress
    {
        DB::transaction(function () use ($address, $dto, $type): void {
            $address->update([
                'name' => $dto->name,
                'default' => $dto->default,
            ]);

            if (!($dto->address instanceof Optional)) {
                $address->address?->update($dto->address->toArray());
            }

            if ($address->default) {
                $this->defaultSet($address, $type);
            }

            $address->increment('change_version');
        });

        return $address;
    }
}
