<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\RegisterRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Propaganistas\LaravelPhone\PhoneNumber;

class RegisterDto extends Dto implements InstantiateFromRequest
{
    private string $name;
    private string $email;
    private string $password;
    private Collection $consents;
    private string|null|Missing $birthday_date;
    private string|null|Missing $phone_country;
    private string|null|Missing $phone_number;
    protected array|Missing $metadata_personal;

    public static function instantiateFromRequest(FormRequest|RegisterRequest $request): self
    {
        $phone = $request->has('phone') && $request->input('phone')
            ? PhoneNumber::make($request->input('phone')) : $request->input('phone', new Missing());
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            password: $request->input('password'),
            consents: new Collection($request->input('consents')),
            birthday_date: $request->input('birthday_date', new Missing()),
            phone_country: $phone instanceof PhoneNumber ? $phone->getCountry() : $phone,
            phone_number: $phone instanceof PhoneNumber ? $phone->formatNational() : $phone,
            metadata_personal: self::mapMetadata($request),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getConsents(): Collection
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

    public function getMetadataPersonal(): Missing|array
    {
        return $this->metadata_personal;
    }

    // @phpstan-ignore-next-line
    private static function mapMetadata(FormRequest|RegisterRequest $request): array|Missing
    {
        $metadata = Collection::make();
        if ($request->has('metadata_personal')) {
            foreach ($request->input('metadata_personal') as $key => $value) {
                $metadata->push(MetadataPersonalDto::manualInit($key, $value));
            }
        }
        return $metadata->isEmpty() ? new Missing() : $metadata->toArray();
    }
}
