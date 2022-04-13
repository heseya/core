<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;

class AddressDto extends Dto implements DtoContract, InstantiateFromRequest
{
    private string|Missing $name;
    private string|Missing $phone;
    private string|Missing $address;
    private string|Missing $vat;
    private string|Missing $zip;
    private string|Missing $city;
    private string|Missing $country;

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            $request->input('name', new Missing()),
            $request->input('phone', new Missing()),
            $request->input('address', new Missing()),
            $request->input('vat', new Missing()),
            $request->input('zip', new Missing()),
            $request->input('city', new Missing()),
            $request->input('country', new Missing()),
        );
    }

    public function getAddressName(): Missing|string
    {
        return $this->name;
    }

    public function getAddressPhone(): Missing|string
    {
        return $this->phone;
    }

    public function getAddressAddress(): Missing|string
    {
        return $this->address;
    }

    public function getAddressVat(): Missing|string
    {
        return $this->vat;
    }

    public function getAddressZip(): Missing|string
    {
        return $this->zip;
    }

    public function getAddressCity(): Missing|string
    {
        return $this->city;
    }

    public function getAddressCountry(): Missing|string
    {
        return $this->country;
    }
}
