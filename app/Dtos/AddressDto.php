<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Illuminate\Http\Request;

class AddressDto implements DtoContract, InstantiateFromRequest
{
    private ?string $addressName;
    private ?string $addressPhone;
    private ?string $addressAddress;
    private ?string $addressVat;
    private ?string $addressZip;
    private ?string $addressCity;
    private ?string $addressCountry;

    public function __construct(
        ?string $addressName,
        ?string $addressPhone,
        ?string $addressAddress,
        ?string $addressVat,
        ?string $addressZip,
        ?string $addressCity,
        ?string $addressCountry
    ) {
        $this->addressName = $addressName;
        $this->addressPhone = $addressPhone;
        $this->addressAddress = $addressAddress;
        $this->addressVat = $addressVat;  // Vat number - NIP
        $this->addressZip = $addressZip;
        $this->addressCity = $addressCity;
        $this->addressCountry = $addressCountry;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getAddressName(),
            'phone' => $this->getAddressPhone(),
            'address' => $this->getAddressAddress(),
            'vat' => $this->getAddressVat(),
            'zip' => $this->getAddressZip(),
            'city' => $this->getAddressCity(),
            'country' => $this->getAddressCountry(),
        ];
    }

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('phone'),
            $request->input('address'),
            $request->input('vat'),
            $request->input('zip'),
            $request->input('city'),
            $request->input('country'),
        );
    }

    public function getAddressName(): ?string
    {
        return $this->addressName;
    }

    public function getAddressPhone(): ?string
    {
        return $this->addressPhone;
    }

    public function getAddressAddress(): ?string
    {
        return $this->addressAddress;
    }

    public function getAddressVat(): ?string
    {
        return $this->addressVat;
    }

    public function getAddressZip(): ?string
    {
        return $this->addressZip;
    }

    public function getAddressCity(): ?string
    {
        return $this->addressCity;
    }

    public function getAddressCountry(): ?string
    {
        return $this->addressCountry;
    }
}
