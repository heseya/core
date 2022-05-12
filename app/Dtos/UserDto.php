<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class UserDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private string|Missing $name;
    private string|Missing $email;
    private string|Missing $password;
    private array|Missing $roles;

    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            email: $request->input('email', new Missing()),
            password: $request->input('password', new Missing()),
            roles: $request->input('roles', new Missing()),
            metadata: self::mapMetadata($request),
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
}
