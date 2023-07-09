<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\PaymentMethodIndexRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodIndexDto extends Dto implements InstantiateFromRequest
{
    private Missing|string $shipping_method_id;
    private Missing|string $order_code;
    private array|Missing $ids;

    public static function instantiateFromRequest(FormRequest|PaymentMethodIndexRequest $request): self
    {
        return new self(
            shipping_method_id: $request->input('shipping_method_id', new Missing()),
            order_code: $request->input('order_code', new Missing()),
            ids: $request->input('ids', new Missing()),
        );
    }

    public function getShippingMethodId(): Missing|string
    {
        return $this->shipping_method_id;
    }

    public function getOrderCode(): Missing|string
    {
        return $this->order_code;
    }

    public function getIds(): array|Missing
    {
        return $this->ids;
    }
}
