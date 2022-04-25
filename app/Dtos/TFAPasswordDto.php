<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\TFAPasswordRequest;
use App\Models\User;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;

class TFAPasswordDto extends Dto implements InstantiateFromRequest
{
    private string $password;
    private User $user;

    public static function instantiateFromRequest(FormRequest|TFAPasswordRequest $request): self
    {
        return new self(
            password: $request->input('password'),
            user: $request->user(),
        );
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
