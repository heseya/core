<?php

namespace App\Services\Contracts;

use App\Dtos\SavedAddressDto;
use App\Models\SavedAddress;

interface SavedAddressServiceContract
{
    public function storeAddress(
        SavedAddressDto $addressDto,
        string $type
    ): SavedAddress;
    public function updateAddress(
        SavedAddress $address,
        SavedAddressDto $addressDto,
        string $type
    ): SavedAddress;
    public function deleteSavedAddress(SavedAddress $address): void;
}
