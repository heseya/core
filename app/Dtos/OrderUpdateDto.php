<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Missing;
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
    private string|Missing|null $shippingMethodId;
    private ?bool $invoiceRequested;
    private string|AddressDto|null|Missing $shippingPlace;
    private ?AddressDto $billingAddress;

    public function __construct(
        ?string $code,
        ?string $email,
        ?string $currency,
        ?string $comment,
        ?string $shippingNumber,
        ?float $shippingPrice,
        ?string $statusId,
        string|Missing $shippingMethodId,
        ?AddressDto $billingAddress,
        string|AddressDto|null|Missing $shippingPlace,
        ?bool $invoiceRequested,
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
            'invoice_request' => $this->getInvoiceRequested(),
            'shipping_place' => $this->getShippingPlace(),
            'billing_address' => $this->getBillingAddress(),
        ];
    }

    public static function instantiateFromRequest(Request $request): self
    {
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

        $shippingPlace = is_array($request->input('shipping_place')) ?
            new AddressDto(
                $request->input('shipping_place.name'),
                $request->input('shipping_place.phone'),
                $request->input('shipping_place.address'),
                $request->input('shipping_place.vat'),
                $request->input('shipping_place.zip'),
                $request->input('shipping_place.city'),
                $request->input('shipping_place.country'),
            ) : $request->input('shipping_place', new Missing());

        return new self(
            $request->input('code'),
            $request->input('email'),
            $request->input('currency', 'PLN'),
            $request->exists('comment') ? ($request->input('comment') ?? '') : null,
            $request->input('shipping_number'),
            $request->input('shipping_price'),
            $request->input('status_id'),
            $request->input('shipping_method_id', new Missing()),
            $billingAddress,
            $shippingPlace,
            $request->input('invoice_requested'),
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

    public function getShippingMethodId(): string|null|Missing
    {
        return $this->shippingMethodId;
    }

    public function getBillingAddress(): ?AddressDto
    {
        return $this->billingAddress;
    }

    public function getInvoiceRequested(): ?bool
    {
        return $this->invoiceRequested;
    }

    public function getShippingPlace(): string|AddressDto|null|Missing
    {
        return $this->shippingPlace;
    }
}
