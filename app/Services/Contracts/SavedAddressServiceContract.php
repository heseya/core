<?php

namespace App\Services\Contracts;

use App\Dtos\SavedAddressDto;
use App\Enums\SavedAddressType;
use App\Models\SavedAddress;
use Domain\User\Dtos\SavedAddressStoreDto;

interface SavedAddressServiceContract
{
    public function storeAddress(
        SavedAddressStoreDto $dto
    ): ?SavedAddress;

    public function updateAddress(
        SavedAddress $address,
        SavedAddressDto $addressDto,
        SavedAddressType $type
    ): SavedAddress;

    public function deleteSavedAddress(SavedAddress $address): void;
}
