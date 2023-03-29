<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class OrderUpdateDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private string|Missing $email;
    private string|null|Missing $comment;
    private string|Missing $shipping_method_id;
    private string|Missing $digital_shipping_method_id;
    private AddressDto|Missing $billing_address;
    private string|null|Missing $shipping_number;
    private string|AddressDto|Missing $shipping_place;
    private bool|Missing $invoice_requested;

    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest|OrderCreateRequest|OrderUpdateRequest $request): self
    {
        return new self(
            email: $request->input('email', new Missing()),
            comment: $request->input('comment', new Missing()),
            shipping_method_id: $request->input('shipping_method_id', new Missing()),
            digital_shipping_method_id: $request->input('digital_shipping_method_id', new Missing()),
            shipping_place: is_array($request->input('shipping_place'))
                ? AddressDto::instantiateFromRequest($request, 'shipping_place.')
                : $request->input('shipping_place', new Missing()) ?? new Missing(),
            billing_address: $request->has('billing_address')
                ? AddressDto::instantiateFromRequest($request, 'billing_address.') : new Missing(),
            metadata: self::mapMetadata($request),
            shipping_number: $request->input('shipping_number', new Missing()),
            invoice_requested: $request->input('invoice_requested', new Missing()),
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

    public function getShippingMethodId(): Missing|string
    {
        return $this->shipping_method_id;
    }

    public function getDigitalShippingMethodId(): Missing|string
    {
        return $this->digital_shipping_method_id;
    }

    public function getShippingPlace(): string|AddressDto|Missing
    {
        return $this->shipping_place;
    }

    public function getBillingAddress(): Missing|AddressDto
    {
        return $this->billing_address;
    }

    public function getInvoiceRequested(): Missing|bool
    {
        return $this->invoice_requested;
    }

    public function getShippingNumber(): Missing|string|null
    {
        return $this->shipping_number;
    }
}
