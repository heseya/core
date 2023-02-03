<?php

namespace App\Dtos;

use App\Http\Requests\PaymentMethodStoreRequest;
use App\Http\Requests\PaymentMethodUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class PaymentMethodDto extends Dto
{
    private string|Missing $name;
    private string|Missing $icon;
    private string|Missing $url;
    private bool|Missing $public;

    public static function instantiateFromRequest(PaymentMethodStoreRequest|PaymentMethodUpdateRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            icon: $request->input('icon', new Missing()),
            url: $request->input('url', new Missing()),
            public: $request->input('public', new Missing()),
        );
    }

    public function getName(): string|Missing
    {
        return $this->name;
    }

    public function getIcon(): string|Missing
    {
        return $this->icon;
    }

    public function getUrl(): string|Missing
    {
        return $this->url;
    }

    public function getPublic(): bool|Missing
    {
        return $this->public;
    }
}
