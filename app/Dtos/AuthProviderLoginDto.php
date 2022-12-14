<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\AuthProviderLoginRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class AuthProviderLoginDto extends Dto implements InstantiateFromRequest
{
    private string $return_url;
    private string|null $ip;
    private string|null $user_agent;
    private array $params;

    public static function instantiateFromRequest(FormRequest|AuthProviderLoginRequest $request): self
    {
        $params = parse_url($request->input('return_url'), PHP_URL_QUERY);
        $returnUrl = Str::before($request->input('return_url'), '?');
        parse_str($params, $params);
        return new self(
            return_url: $returnUrl,
            ip: $request->ip(),
            user_agent: $request->userAgent(),
            params: $params,
        );
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

    public function getParams(): array
    {
        return $this->params;
    }
}
