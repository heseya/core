<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Illuminate\Http\Request;

class AddressDto implements DtoContract, InstantiateFromRequest
{
    private ?string $deliveryAddressName;
    private ?string $deliveryAddressPhone;
    private ?string $deliveryAddressAddress;
    private ?string $deliveryAddressNip;
    private ?string $deliveryAddressZip;
    private ?string $deliveryAddressCity;
    private ?string $deliveryAddressCountry;

    private ?string $invoiceAddressName;
    private ?string $invoiceAddressPhone;
    private ?string $invoiceAddressAddress;
    private ?string $invoiceAddressNip;
    private ?string $invoiceAddressZip;
    private ?string $invoiceAddressCity;
    private ?string $invoiceAddressCountry;

    public function __construct(
        ?string $deliveryAddressName,
        ?string $deliveryAddressPhone,
        ?string $deliveryAddressAddress,
        ?string $deliveryAddressVat,
        ?string $deliveryAddressZip,
        ?string $deliveryAddressCity,
        ?string $deliveryAddressCountry,
        ?string $invoiceAddressName,
        ?string $invoiceAddressPhone,
        ?string $invoiceAddressAddress,
        ?string $invoiceAddressVat,
        ?string $invoiceAddressZip,
        ?string $invoiceAddressCity,
        ?string $invoiceAddressCountry
    ) {
        $this->deliveryAddressName = $deliveryAddressName;
        $this->deliveryAddressPhone = $deliveryAddressPhone;
        $this->deliveryAddressAddress = $deliveryAddressAddress;
        $this->deliveryAddressNip = $deliveryAddressVat;  // Vat number - NIP
        $this->deliveryAddressZip = $deliveryAddressZip;
        $this->deliveryAddressCity = $deliveryAddressCity;
        $this->deliveryAddressCountry = $deliveryAddressCountry;

        $this->invoiceAddressName = $invoiceAddressName;
        $this->invoiceAddressPhone = $invoiceAddressPhone;
        $this->invoiceAddressAddress = $invoiceAddressAddress;
        $this->invoiceAddressNip = $invoiceAddressVat;
        $this->invoiceAddressZip = $invoiceAddressZip;
        $this->invoiceAddressCity = $invoiceAddressCity;
        $this->invoiceAddressCountry = $invoiceAddressCountry;
    }

    public function toArray(): array
    {
        return [
            'delivery_address' => [
                'name' => $this->getDeliveryAddressName(),
                'phone' => $this->getDeliveryAddressPhone(),
                'address' => $this->getDeliveryAddressAddress(),
                'nip' => $this->getDeliveryAddressNip(),
                'zip' => $this->getDeliveryAddressZip(),
                'city' => $this->getDeliveryAddressCity(),
                'country' => $this->getDeliveryAddressCountry(),
            ],
            'invoice_address' => [
                'name' => $this->getInvoiceAddressName(),
                'phone' => $this->getInvoiceAddressPhone(),
                'address' => $this->getInvoiceAddressAddress(),
                'nip' => $this->getInvoiceAddressNip(),
                'zip' => $this->getInvoiceAddressZip(),
                'city' => $this->getInvoiceAddressCity(),
                'country' => $this->getInvoiceAddressCountry(),
            ],
        ];
    }

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            $request->input('delivery_address.name'),
            $request->input('delivery_address.phone'),
            $request->input('delivery_address.address'),
            $request->input('delivery_address.vat'),
            $request->input('delivery_address.zip'),
            $request->input('delivery_address.city'),
            $request->input('delivery_address.country'),
            $request->input('invoice_address.name'),
            $request->input('invoice_address.phone'),
            $request->input('invoice_address.address'),
            $request->input('invoice_address.vat'),
            $request->input('invoice_address.zip'),
            $request->input('invoice_address.city'),
            $request->input('invoice_address.country'),
        );
    }

    public function getDeliveryAddressName(): ?string
    {
        return $this->deliveryAddressName;
    }

    public function getDeliveryAddressPhone(): ?string
    {
        return $this->deliveryAddressPhone;
    }

    public function getDeliveryAddressAddress(): ?string
    {
        return $this->deliveryAddressAddress;
    }

    public function getDeliveryAddressNip(): ?string
    {
        return $this->deliveryAddressNip;
    }

    public function getDeliveryAddressZip(): ?string
    {
        return $this->deliveryAddressZip;
    }

    public function getDeliveryAddressCity(): ?string
    {
        return $this->deliveryAddressCity;
    }

    public function getDeliveryAddressCountry(): ?string
    {
        return $this->deliveryAddressCountry;
    }

    public function getInvoiceAddressName(): ?string
    {
        return $this->invoiceAddressName;
    }

    public function getInvoiceAddressPhone(): ?string
    {
        return $this->invoiceAddressPhone;
    }

    public function getInvoiceAddressAddress(): ?string
    {
        return $this->invoiceAddressAddress;
    }

    public function getInvoiceAddressNip(): ?string
    {
        return $this->invoiceAddressNip;
    }

    public function getInvoiceAddressZip(): ?string
    {
        return $this->invoiceAddressZip;
    }

    public function getInvoiceAddressCity(): ?string
    {
        return $this->invoiceAddressCity;
    }

    public function getInvoiceAddressCountry(): ?string
    {
        return $this->invoiceAddressCountry;
    }
}
