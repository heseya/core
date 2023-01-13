<?php

namespace App\Services;

use App\Dtos\SavedAddressDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Address;
use App\Models\SavedAddress;
use App\Services\Contracts\SavedAddressServiceContract;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SavedAddressService implements SavedAddressServiceContract
{
    public function storeAddress(SavedAddressDto $addressDto, int $type): ?SavedAddress
    {
        $savedAddress = null;

        DB::transaction(function () use ($addressDto, &$savedAddress, $type): void {
            $address = Address::create([
                'name' => $addressDto->getAddress()['name'],
                'phone' => $addressDto->getAddress()['phone'],
                'address' => $addressDto->getAddress()['address'],
                'zip' => $addressDto->getAddress()['zip'],
                'city' => $addressDto->getAddress()['city'],
                'country' => $addressDto->getAddress()['country'],
                'vat' => $addressDto->getAddress()['vat'],
            ]);

            $savedAddress = SavedAddress::create([
                'default' => $addressDto->getDefault(),
                'name' => $addressDto->getName(),
                'user_id' => Auth::id(),
                'address_id' => $address->getKey(),
                'type' => $type,
            ]);
        });

        if ($savedAddress?->default) {
            $this->defaultSet($savedAddress, $type);
        }

        return $savedAddress;
    }

    public function updateAddress(
        SavedAddress $address,
        SavedAddressDto $addressDto,
        int $type
    ): SavedAddress {
        if (Auth::id() !== $address->user_id) {
            throw new AuthenticationException();
        }

        DB::transaction(function () use ($address, $addressDto, $type): void {
            $address->update([
                'default' => $addressDto->getDefault(),
                'name' => $addressDto->getName(),
            ]);

            $address->address?->update([
                'name' => $addressDto->getAddress()['name'],
                'phone' => $addressDto->getAddress()['phone'],
                'address' => $addressDto->getAddress()['address'],
                'zip' => $addressDto->getAddress()['zip'],
                'city' => $addressDto->getAddress()['city'],
                'country' => $addressDto->getAddress()['country'],
                'vat' => $addressDto->getAddress()['vat'],
            ]);

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

    public function defaultSet(SavedAddress $address, int $type): void
    {
        SavedAddress::where('id', '!=', $address->getKey())
            ->where('user_id', '=', $address->user_id)
            ->where('type', '=', $type)
            ->update(['default' => false]);
    }
}
