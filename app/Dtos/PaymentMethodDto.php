<?php

namespace App\Dtos;

use App\Http\Requests\PaymentMethodStoreRequest;
use App\Http\Requests\PaymentMethodUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class PaymentMethodDto extends Dto
{
    private Missing|string $name;
    private Missing|string $icon;
    private Missing|string $url;
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

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getIcon(): Missing|string
    {
        return $this->icon;
    }

    public function getUrl(): Missing|string
    {
        return $this->url;
    }

    public function getPublic(): bool|Missing
    {
        return $this->public;
    }
}
