<?php

namespace App\Dtos;

use App\Http\Requests\TFAPasswordRequest;
use App\Models\User;
use Heseya\Dto\Dto;

class TFAPasswordDto extends Dto
{
    private string $password;
    private User $user;

    public static function fromFormRequest(TFAPasswordRequest $request): self
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
