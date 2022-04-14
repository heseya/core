<?php

namespace App\Dtos;

use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class AddressDto extends Dto
{
    private string|Missing $name;
    private string|Missing $address;
    private string|Missing $vat;
    private string|Missing $zip;
    private string|Missing $city;
    private string|Missing $country;
    private string|Missing $phone;

    public static function fromFormRequest(OrderCreateRequest|OrderUpdateRequest $request, ?string $prefix = ''): self
    {
        return new self(
            name: $request->input($prefix . 'name', new Missing()),
            address: $request->input($prefix . 'address', new Missing()),
            vat: $request->input($prefix . 'vat', new Missing()),
            zip: $request->input($prefix . 'zip', new Missing()),
            city: $request->input($prefix . 'city', new Missing()),
            country: $request->input($prefix . 'country', new Missing()),
            phone: $request->input($prefix . 'phone', new Missing()),
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getAddress(): Missing|string
    {
        return $this->address;
    }

    public function getVat(): Missing|string
    {
        return $this->vat;
    }

    public function getZip(): Missing|string
    {
        return $this->zip;
    }

    public function getCity(): Missing|string
    {
        return $this->city;
    }

    public function getCountry(): Missing|string
    {
        return $this->country;
    }

    public function getPhone(): Missing|string
    {
        return $this->phone;
    }
}
