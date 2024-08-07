<?php

namespace App\Dtos;

use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\PhoneNumber;

class UserCreateDto extends UserDto
{
    private string $password;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        $phone = $request->has('phone') && $request->input('phone')
            ? new PhoneNumber($request->input('phone')) : $request->input('phone', new Missing());

        return new self(
            name: $request->input('name', new Missing()),
            email: $request->input('email', new Missing()),
            password: $request->input('password'),
            roles: $request->input('roles', new Missing()),
            metadata: self::mapMetadata($request),
            birthday_date: $request->input('birthday_date', new Missing()),
            phone_country: $phone instanceof PhoneNumber ? $phone->getCountry() : $phone,
            phone_number: $phone instanceof PhoneNumber ? $phone->formatNational() : $phone,
        );
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
