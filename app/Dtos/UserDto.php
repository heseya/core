<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\PhoneNumber;

class UserDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private string|Missing $name;
    private string|Missing $email;
    private string|Missing $password;
    private array|Missing $roles;
    private string|null|Missing $birthday_date;
    private string|null|Missing $phone_country;
    private string|null|Missing $phone_number;

    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        $phone = $request->has('phone') && $request->input('phone')
            ? PhoneNumber::make($request->input('phone')) : $request->input('phone', new Missing());

        return new self(
            name: $request->input('name', new Missing()),
            email: $request->input('email', new Missing()),
            password: $request->input('password', new Missing()),
            roles: $request->input('roles', new Missing()),
            metadata: self::mapMetadata($request),
            birthday_date: $request->input('birthday_date', new Missing()),
            phone_country: $phone instanceof PhoneNumber ? $phone->getCountry() : $phone,
            phone_number: $phone instanceof PhoneNumber ? $phone->formatNational() : $phone,
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getEmail(): Missing|string
    {
        return $this->email;
    }

    public function getPassword(): Missing|string
    {
        return $this->password;
    }

    public function getRoles(): Missing|array
    {
        return $this->roles;
    }

    public function getBirthdayDate(): Missing|string|null
    {
        return $this->birthday_date;
    }

    public function getPhoneCountry(): Missing|string|null
    {
        return $this->phone_country;
    }

    public function getPhoneNumber(): Missing|string|null
    {
        return $this->phone_number;
    }

}
