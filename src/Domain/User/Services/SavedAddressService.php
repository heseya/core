<?php

declare(strict_types=1);

namespace Domain\User\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\SavedAddressType;
use App\Exceptions\ClientException;
use App\Models\Address;
use App\Models\SavedAddress;
use Domain\User\Dtos\SavedAddressStoreDto;
use Domain\User\Dtos\SavedAddressUpdateDto;
use Domain\User\Services\Contracts\SavedAddressServiceContract;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

final class SavedAddressService implements SavedAddressServiceContract
{
    public function storeAddress(SavedAddressStoreDto $dto): ?SavedAddress
    {
        $savedAddress = DB::transaction(function () use ($dto): SavedAddress {
            $address = Address::create($dto->address->toArray());

            return SavedAddress::create([
                'default' => $dto->default,
                'name' => $dto->name,
                'type' => $dto->type,
                'user_id' => Auth::id(),
                'address_id' => $address->getKey(),
            ]);
        });

        if ($savedAddress->default) {
            $this->defaultSet($savedAddress, $dto->type);
        }

        return $savedAddress;
    }

    public function updateAddress(
        SavedAddress $address,
        SavedAddressUpdateDto $addressDto,
        SavedAddressType $type
    ): SavedAddress {
        if (Auth::id() !== $address->user_id) {
            throw new AuthenticationException();
        }

        DB::transaction(function () use ($address, $addressDto, $type): void {
            $address->update([
                'default' => $addressDto->default,
                'name' => $addressDto->name,
            ]);

            if (!($addressDto->address instanceof Optional)) {
                $address->address?->update($addressDto->address->toArray());
            }

            if ($address->default) {
                $this->defaultSet($address, $type);
            }
        });

        return $address;
    }

    public function deleteSavedAddress(SavedAddress $address): void
    {
        if (Auth::id() !== $address->user_id) {
            throw new AuthenticationException();
        }

        if ($address->default) {
            throw new ClientException(Exceptions::CLIENT_REMOVE_DEFAULT_ADDRESS);
        }

        $address->delete();
    }

    public function defaultSet(SavedAddress $address, SavedAddressType $type): void
    {
        SavedAddress::where('id', '!=', $address->getKey())
            ->where('user_id', '=', $address->user_id)
            ->where('type', '=', $type->value)
            ->update(['default' => false]);
    }
}
