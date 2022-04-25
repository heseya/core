<?php

namespace App\Dtos;

use App\Http\Requests\SavedAddressStoreRequest;
use App\Http\Requests\SavedAddressUpdateRequest;
use Heseya\Dto\Dto;

class SavedAddressDto extends Dto
{
    private bool $default;
    private string $name;
    private array $address;

    public static function instantiateFromRequest(SavedAddressStoreRequest|SavedAddressUpdateRequest $request): self
    {
        return new self(
            default: $request->input('default'),
            name: $request->input('name'),
            type: $request->input('type'),
            address: [
                'name' => $request->input('address.name'),
                'phone' => $request->input('address.phone'),
                'address' => $request->input('address.address'),
                'zip' => $request->input('address.zip'),
                'city' => $request->input('address.city'),
                'country' => $request->input('address.country'),
                'vat' => $request->input('address.vat'),
            ],
        );
    }

    public function getDefault(): bool
    {
        return $this->default;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): array
    {
        return $this->address;
    }
}
