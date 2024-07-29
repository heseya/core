<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class AddressDto extends Dto implements InstantiateFromRequest
{
    private Missing|string|null $name;
    private Missing|string|null $company_name;
    private Missing|string|null $address;
    private Missing|string|null $vat;
    private Missing|string|null $zip;
    private Missing|string|null $city;
    private Missing|string|null $country;
    private Missing|string|null $phone;

    public static function instantiateFromRequest(
        FormRequest|OrderCreateRequest|OrderUpdateRequest $request,
        ?string $prefix = '',
    ): self {
        return new self(
            name: $request->input($prefix . 'name', new Missing()),
            company_name: $request->input($prefix . 'company_name', new Missing()),
            address: $request->input($prefix . 'address', new Missing()),
            vat: $request->input($prefix . 'vat', new Missing()),
            zip: $request->input($prefix . 'zip', new Missing()),
            city: $request->input($prefix . 'city', new Missing()),
            country: $request->input($prefix . 'country', new Missing()),
            phone: $request->input($prefix . 'phone', new Missing()),
        );
    }

    public function getName(): Missing|string|null
    {
        return $this->name;
    }

    public function getCompanyName(): Missing|string|null
    {
        return $this->company_name;
    }

    public function getAddress(): Missing|string|null
    {
        return $this->address;
    }

    public function getVat(): Missing|string|null
    {
        return $this->vat;
    }

    public function getZip(): Missing|string|null
    {
        return $this->zip;
    }

    public function getCity(): Missing|string|null
    {
        return $this->city;
    }

    public function getCountry(): Missing|string|null
    {
        return $this->country;
    }

    public function getPhone(): Missing|string|null
    {
        return $this->phone;
    }
}
