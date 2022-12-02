<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

// TODO rozdzielić na Store i Update, bo są różnice w polach i tak
class OrderDto extends CartOrderDto implements InstantiateFromRequest
{
    use MapMetadata;

    private string|Missing $email;
    private string|null|Missing $comment;
    private string|Missing $shipping_method_id;
    private array|Missing $items;
    private AddressDto|Missing $delivery_address;
    private AddressDto|Missing $invoice_address;
    private array|Missing $coupons;
    private array|Missing $sale_ids;
    private string|null|Missing $shipping_number;

    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest|OrderCreateRequest|OrderUpdateRequest $request): self
    {
        $orderProducts = $request->input('items', new Missing());
        $items = [];
        if (!$orderProducts instanceof Missing) {
            foreach ($orderProducts as $orderProduct) {
                array_push($items, OrderProductDto::fromArray($orderProduct));
            }
        }

        return new self(
            email: $request->input('email', new Missing()),
            comment: $request->input('comment', new Missing()),
            shipping_method_id: $request->input('shipping_method_id', new Missing()),
            items: $orderProducts instanceof Missing ? $orderProducts : $items,
            delivery_address: $request->has('delivery_address')
                ? AddressDto::instantiateFromRequest($request, 'delivery_address.') : new Missing(),
            invoice_address: $request->has('invoice_address')
                ? AddressDto::instantiateFromRequest($request, 'invoice_address.') : new Missing(),
            coupons: $request->input('coupons', new Missing()),
            sale_ids: $request->input('sale_ids', new Missing()),
            metadata: self::mapMetadata($request),
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

    public function getShippingMethodId(): Missing|string
    {
        return $this->shipping_method_id;
    }

    public function getItems(): Missing|array
    {
        return $this->items;
    }

    public function getDeliveryAddress(): Missing|AddressDto
    {
        return $this->delivery_address;
    }

    public function getInvoiceAddress(): Missing|AddressDto
    {
        return $this->invoice_address;
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
        if (!$this->items instanceof Missing) {
            /** @var OrderProductDto $item */
            foreach ($this->items as $item) {
                array_push($result, $item->getProductId());
            }
        }
        return $result;
    }

    public function getCartLength(): int|float
    {
        $length = 0;
        if (!$this->items instanceof Missing) {
            /** @var OrderProductDto $item */
            foreach ($this->items as $item) {
                $length += $item->getQuantity();
            }
        }
        return $length;
    }

    public function getShippingNumber(): Missing|string|null
    {
        return $this->shipping_number;
    }
}
