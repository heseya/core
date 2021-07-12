<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Illuminate\Http\Request;

class OrderUpdateDto implements DtoContract, InstantiateFromRequest
{
    private ?string $code;
    private string $email;
    private ?string $currency;
    private ?string $comment;
    private ?string $shippingNumber;
    private ?float $shippingPrice;
    private ?string $statusId;
    private ?string $shippingMethodId;
    private AddressDto $deliveryAddress;
    private ?AddressDto $invoiceAddress;

    public function __construct(
        ?string $code,
        string $email,
        ?string $currency,
        ?string $comment,
        ?string $shippingNumber,
        ?float $shippingPrice,
        ?string $statusId,
        ?string $shippingMethodId,
        AddressDto $deliveryAddress,
        AddressDto $invoiceAddress
    ) {
        $this->code = $code;
        $this->email = $email;
        $this->currency = $currency;
        $this->comment = $comment;
        $this->shippingNumber = $shippingNumber;
        $this->shippingPrice = $shippingPrice;
        $this->statusId = $statusId;
        $this->shippingMethodId = $shippingMethodId;
        $this->deliveryAddress = $deliveryAddress;
        $this->invoiceAddress = $invoiceAddress;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'email' => $this->getEmail(),
            'currency' => $this->getCurrency(),
            'comment' => $this->getComment(),
            'shipping_number' => $this->getShippingNumber(),
            'shipping_price' => $this->getShippingPrice(),
            'status_id' => $this->getStatusId(),
            'shipping_method_id' => $this->getShippingMethodId(),
            'delivery_address' => $this->getDeliveryAddress(),
            'invoice_address' => $this->getInvoiceAddress(),
        ];
    }

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            $request->input('code'),
            $request->input('email'),
            $request->input('currency', 'PLN'),
            $request->input('comment'),
            $request->input('shipping_number'),
            $request->input('shipping_price'),
            $request->input('status_id'),
            $request->input('shipping_method'),
            new AddressDto(
                $request->input('delivery_address.name'),
                $request->input('delivery_address.phone'),
                $request->input('delivery_address.address'),
                $request->input('delivery_address.vat'),
                $request->input('delivery_address.zip'),
                $request->input('delivery_address.city'),
                $request->input('delivery_address.country'),
            ),
            new AddressDto(
                $request->input('invoice_address.name'),
                $request->input('invoice_address.phone'),
                $request->input('invoice_address.address'),
                $request->input('invoice_address.vat'),
                $request->input('invoice_address.zip'),
                $request->input('invoice_address.city'),
                $request->input('invoice_address.country'),
            ),
        );
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getShippingNumber(): ?string
    {
        return $this->shippingNumber;
    }

    public function getShippingPrice(): ?float
    {
        return $this->shippingPrice;
    }

    public function getStatusId(): ?string
    {
        return $this->statusId;
    }

    public function getShippingMethodId(): ?string
    {
        return $this->shippingMethodId;
    }

    public function getDeliveryAddress(): AddressDto
    {
        return $this->deliveryAddress;
    }

    public function getInvoiceAddress(): ?AddressDto
    {
        return $this->invoiceAddress;
    }
}
