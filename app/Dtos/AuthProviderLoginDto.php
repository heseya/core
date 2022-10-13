<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\AuthProviderLoginRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class AuthProviderLoginDto extends Dto implements InstantiateFromRequest
{
    private string $code;
    private string $return_url;
    private string|null $ip;
    private string|null $user_agent;

    public static function instantiateFromRequest(FormRequest|AuthProviderLoginRequest $request): self
    {
        return new self(
            code: $request->input('code'),
            return_url: $request->input('return_url'),
            ip: $request->ip(),
            user_agent: $request->userAgent(),
        );
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getReturnUrl(): Missing|string
    {
        return $this->return_url;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }
}
