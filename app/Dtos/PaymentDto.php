<?php

namespace App\Dtos;

use App\Http\Requests\PaymentStoreRequest;
use App\Http\Requests\PaymentUpdateRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class PaymentDto extends Dto
{
    private string|Missing $external_id;
    private string|Missing $method_id;
    private string|Missing $status;
    private float|Missing $amount;

    public static function fromFormRequest(PaymentStoreRequest|PaymentUpdateRequest $request)
    {
        return new self(
            external_id: $request->input('external_id'),
            method_id: $request->input('method_id'),
            status: $request->input('status'),
            amount: $request->input('amount'),
        );
    }

    public function getExternalId(): string|Missing
    {
        return $this->external_id;
    }

    public function getMethodId(): string|Missing
    {
        return $this->method_id;
    }

    public function getStatus(): string|Missing
    {
        return $this->status;
    }

    public function getAmount(): float|Missing
    {
        return $this->amount;
    }
}
