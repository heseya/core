<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;
use Propaganistas\LaravelPhone\PhoneNumber;

class UpdateProfileDto extends Dto implements InstantiateFromRequest
{
    private string|Missing $name;
    private UserPreferencesDto $preferences;
    private array $consents;
    private string|null|Missing $birthday_date;
    private string|null|Missing $phone_country;
    private string|null|Missing $phone_number;

    public static function instantiateFromRequest(Request $request): self
    {
        $phone = $request->has('phone') && $request->input('phone')
            ? new PhoneNumber($request->input('phone')) : $request->input('phone', new Missing());

        return new self(
            name: $request->input('name', new Missing()),
            preferences: UserPreferencesDto::instantiateFromRequest($request),
            consents: $request->input('consents', []),
            birthday_date: $request->input('birthday_date', new Missing()),
            phone_country: $phone instanceof PhoneNumber ? $phone->getCountry() : $phone,
            phone_number: $phone instanceof PhoneNumber ? $phone->formatNational() : $phone,
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getPreferences(): UserPreferencesDto
    {
        return $this->preferences;
    }

    public function getConsents(): array
    {
        return $this->consents;
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
