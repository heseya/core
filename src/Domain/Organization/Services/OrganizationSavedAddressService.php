<?php

declare(strict_types=1);

namespace Domain\Organization\Services;

use App\Enums\SavedAddressType;
use App\Models\Address;
use Domain\Organization\Dtos\OrganizationSavedAddressCreateDto;
use Domain\Organization\Models\OrganizationSavedAddress;
use Illuminate\Support\Facades\DB;

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
}
