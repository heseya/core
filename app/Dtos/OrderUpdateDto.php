<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class OrderUpdateDto extends Dto implements InstantiateFromRequest
{
    private string|Missing $email;
    private string|null|Missing $comment;
    private string|null|Missing $shipping_number;
    private AddressDto|Missing $delivery_address;
    private AddressDto|Missing $invoice_address;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            email: $request->input('email', new Missing()),
            comment: $request->input('comment', new Missing()),
            delivery_address: $request->has('delivery_address')
                ? AddressDto::instantiateFromRequest($request, 'delivery_address.') : new Missing(),
            invoice_address: $request->has('invoice_address')
                ? AddressDto::instantiateFromRequest($request, 'invoice_address.') : new Missing(),
            shipping_number: $request->input('shipping_number', new Missing()),
        );
    }

    public function getEmail(): Missing|string
    {
        return $this->email;
    }

    public function getComment(): Missing|string|null
    {
        return $this->comment;
    }

    public function getShippingNumber(): Missing|null|string
    {
        return $this->shipping_number;
    }

    public function getDeliveryAddress(): Missing|AddressDto
    {
        return $this->delivery_address;
    }

    public function getInvoiceAddress(): Missing|AddressDto
    {
        return $this->invoice_address;
    }
}
