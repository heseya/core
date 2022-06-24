<?php

namespace App\Services\Contracts;

use App\Dtos\SavedAddressDto;
use App\Enums\SavedAddressType;
use App\Models\SavedAddress;

interface SavedAddressServiceContract
{
    public function storeAddress(
        SavedAddressDto $addressDto,
        SavedAddressType $type
    ): SavedAddress;
    public function updateAddress(
        SavedAddress $address,
        SavedAddressDto $addressDto,
        SavedAddressType $type
    ): SavedAddress;
    public function deleteSavedAddress(SavedAddress $address): void;
}
