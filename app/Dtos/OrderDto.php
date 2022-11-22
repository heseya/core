<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class OrderDto extends CartOrderDto implements InstantiateFromRequest
{
    use MapMetadata;

    private string|Missing $email;
    private string|null|Missing $comment;
    private string|Missing $shipping_method_id;
    private string|Missing $digital_shipping_method_id;
    private array|Missing $items;
    private AddressDto|Missing $billing_address;
    private array|Missing $coupons;
    private array|Missing $sale_ids;
    private string|null|Missing $shipping_number;
    private string|AddressDto|Missing $shipping_place;
    private bool|Missing $invoice_requested;

    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest|OrderCreateRequest|OrderUpdateRequest $request): self
    {
        $orderProducts = $request->input('items', new Missing());
        $items = [];
        if (!$orderProducts instanceof Missing) {
            foreach ($orderProducts as $orderProduct) {
                $items[] = OrderProductDto::fromArray($orderProduct);
            }
        }

        return new self(
            email: $request->input('email', new Missing()),
            comment: $request->input('comment', new Missing()),
            shipping_method_id: $request->input('shipping_method_id', new Missing()),
            digital_shipping_method_id: $request->input('digital_shipping_method_id', new Missing()),
            items: $orderProducts instanceof Missing ? $orderProducts : $items,
            shipping_place: is_array($request->input('shipping_place'))
                ? AddressDto::instantiateFromRequest($request, 'shipping_place.')
                : $request->input('shipping_place', new Missing()) ?? new Missing(),
            billing_address: $request->has('billing_address')
                ? AddressDto::instantiateFromRequest($request, 'billing_address.') : new Missing(),
            coupons: $request->input('coupons', new Missing()),
            sale_ids: $request->input('sale_ids', new Missing()),
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

    public function getItems(): Missing|array
    {
        return $this->items;
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

    public function getCoupons(): Missing|array
    {
        return $this->coupons;
    }

    public function getSaleIds(): Missing|array
    {
        return $this->sale_ids;
    }

    public function getProductIds(): array
    {
        $result = [];
        /** @var OrderProductDto $item */
        foreach ($this->items as $item) {
            $result[] = $item->getProductId();
        }
        return $result;
    }

    public function getCartLength(): int
    {
        $length = 0;
        /** @var OrderProductDto $item */
        foreach ($this->items as $item) {
            $length += $item->getQuantity();
        }
        return $length;
    }

    public function getShippingNumber(): Missing|string|null
    {
        return $this->shipping_number;
    }
}
