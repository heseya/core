<?php

namespace App\Dtos;

use App\Http\Requests\RegisterRequest;
use Heseya\Dto\Dto;

class RegisterDto extends Dto
{
    private string $name;
    private string $email;
    private string $password;

    public static function fromFormRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            password: $request->input('password'),
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
}
