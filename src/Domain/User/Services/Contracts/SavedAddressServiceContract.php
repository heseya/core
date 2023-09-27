<?php

declare(strict_types=1);

namespace Domain\User\Services\Contracts;

use App\Enums\SavedAddressType;
use App\Models\SavedAddress;
use Domain\User\Dtos\SavedAddressStoreDto;
use Domain\User\Dtos\SavedAddressUpdateDto;

interface SavedAddressServiceContract
{
    public function storeAddress(
        SavedAddressStoreDto $dto,
    ): ?SavedAddress;

    public function updateAddress(
        SavedAddress $address,
        SavedAddressUpdateDto $addressDto,
        SavedAddressType $type,
    ): SavedAddress;

    public function deleteSavedAddress(SavedAddress $address): void;
}
