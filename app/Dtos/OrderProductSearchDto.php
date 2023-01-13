<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\OrderProductSearchRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class OrderProductSearchDto extends Dto implements InstantiateFromRequest
{
    private bool|Missing $shipping_digital;

    public static function instantiateFromRequest(FormRequest|OrderProductSearchRequest $request): self
    {
        return new self(
            shipping_digital: $request->has('shipping_digital')
                ? $request->boolean('shipping_digital') : new Missing(),
        );
    }

    public function getShippingDigital(): bool|null|Missing
    {
        return $this->shipping_digital;
    }
}
