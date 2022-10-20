<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\RegisterRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class RegisterDto extends Dto implements InstantiateFromRequest
{
    private string $name;
    private string $email;
    private string $password;
    private Collection $consents;

    public static function instantiateFromRequest(FormRequest|RegisterRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            password: $request->input('password'),
            consents: new Collection($request->input('consents')),
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
}
