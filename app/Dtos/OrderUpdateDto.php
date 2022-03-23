<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use App\Enums\ShippingType;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;

class OrderUpdateDto implements DtoContract, InstantiateFromRequest
{
    private ?string $code;
    private ?string $email;
    private ?string $currency;
    private ?string $comment;
    private ?string $shippingNumber;
    private ?float $shippingPrice;
    private ?string $statusId;
    private ?string $shippingMethodId;
    private ?AddressDto $shippingAddress;
    private ?bool $invoiceRequested;
    private string|AddressDto|null $shippingPlace;
    private ?AddressDto $billingAddress;

    public function __construct(
        ?string $code,
        ?string $email,
        ?string $currency,
        ?string $comment,
        ?string $shippingNumber,
        ?float $shippingPrice,
        ?string $statusId,
        ?string $shippingMethodId,
        ?AddressDto $billingAddress,
        ?AddressDto $shippingAddress,
        ?bool $invoiceRequested,
        string|AddressDto|null $shippingPlace,
    ) {
        $this->code = $code;
        $this->email = $email;
        $this->currency = $currency;
        $this->comment = $comment;
        $this->shippingNumber = $shippingNumber;
        $this->shippingPrice = $shippingPrice;
        $this->statusId = $statusId;
        $this->shippingMethodId = $shippingMethodId;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->invoiceRequested = $invoiceRequested;
        $this->shippingPlace = $shippingPlace;
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
            'shipping_address' => $this->getShippingAddress(),
            'invoice_request' => $this->getInvoiceRequested(),
            'shipping_place' => $this->getShippingPlace(),
            'billing_address' => $this->getBillingAddress(),
        ];
    }

    public static function instantiateFromRequest(Request $request): self
    {
        $shippingAddress = $request->exists('shipping_address') ?
            new AddressDto(
                $request->input('shipping_address.name'),
                $request->input('shipping_address.phone'),
                $request->input('shipping_address.address'),
                $request->input('shipping_address.vat'),
                $request->input('shipping_address.zip'),
                $request->input('shipping_address.city'),
                $request->input('shipping_address.country'),
            ) : null;

        $billingAddress = $request->exists('billing_address') ?
            new AddressDto(
                $request->input('billing_address.name'),
                $request->input('billing_address.phone'),
                $request->input('billing_address.address'),
                $request->input('billing_address.vat'),
                $request->input('billing_address.zip'),
                $request->input('billing_address.city'),
                $request->input('billing_address.country'),
            ) : null;

        $shippingMethod = ShippingMethod::find($request->input('shipping_method_id'));

        $shippingPlace = $shippingMethod !== null ?
            match ($shippingMethod->shipping_type) {
                ShippingType::ADDRESS, ShippingType::POINT => $shippingAddress,
                ShippingType::POINT_EXTERNAL => $request->input('shipping_place'),
                default => null,
            }
        : null;

        return new self(
            $request->input('code'),
            $request->input('email'),
            $request->input('currency', 'PLN'),
            $request->exists('comment') ? ($request->input('comment') ?? '') : null,
            $request->input('shipping_number'),
            $request->input('shipping_price'),
            $request->input('status_id'),
            $request->input('shipping_method_id'),
            $billingAddress,
            $shippingAddress,
            $request->input('invoice_requested'),
            $shippingPlace ?? null,
        );
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getEmail(): ?string
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

    public function getShippingAddress(): ?AddressDto
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): ?AddressDto
    {
        return $this->billingAddress;
    }

    public function getInvoiceRequested(): ?bool
    {
        return $this->invoiceRequested;
    }

    public function getShippingPlace(): string|AddressDto|null
    {
        return $this->shippingPlace;
    }
}
