<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Http\Request;

class PaymentDto extends Dto implements InstantiateFromRequest
{
    private Missing|string $external_id;
    private Missing|string $method_id;
    private Missing|string $order_id;
    private Missing|string $status;
    private float|Missing $amount;

    public static function instantiateFromRequest(Request $request): InstantiateFromRequest
    {
        return new self(
            external_id: $request->input('external_id', new Missing()),
            method_id: $request->input('method_id', new Missing()),
            order_id: $request->input('order_id', new Missing()),
            status: $request->input('status', new Missing()),
            amount: $request->input('amount', new Missing()),
        );
    }

    public function getExternalId(): Missing|string
    {
        return $this->external_id;
    }

    public function getMethodId(): Missing|string
    {
        return $this->method_id;
    }

    public function getOrderId(): Missing|string
    {
        return $this->order_id;
    }

    public function getStatus(): Missing|string
    {
        return $this->status;
    }

    public function getAmount(): float|Missing
    {
        return $this->amount;
    }
}
