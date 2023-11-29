<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Traits\MapMetadata;
use Domain\Currency\Currency;
use Domain\Language\LanguageService;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

class OrderDto extends CartOrderDto implements InstantiateFromRequest
{
    use MapMetadata;

    public function __construct(
        public readonly Currency $currency,
        public readonly Missing|string $email,
        public readonly Missing|string|null $comment,
        public readonly Missing|string $shipping_method_id,
        public readonly Missing|string $digital_shipping_method_id,
        public readonly array|Missing $items,
        public readonly AddressDto|Missing $billing_address,
        public readonly array|Missing $coupons,
        public readonly array|Missing $sale_ids,
        public readonly Missing|string|null $shipping_number,
        public readonly AddressDto|Missing|string $shipping_place,
        public readonly bool|Missing $invoice_requested,
        public readonly array|Missing $metadata,
        public readonly string $sales_channel_id,
        public readonly string $language,
    ) {}

    public static function instantiateFromRequest(FormRequest|OrderCreateRequest|OrderUpdateRequest $request): self
    {
        $orderProducts = $request->input('items', []);
        $items = [];
        if (!$orderProducts instanceof Missing) {
            foreach ($orderProducts as $orderProduct) {
                $items[] = OrderProductDto::fromArray($orderProduct);
            }
        }

        /** @var ?Currency $currency */
        $currency = $request->enum('currency', Currency::class);

        if ($currency === null) {
            throw new ServerException(Exceptions::SERVER_PRICE_UNKNOWN_CURRENCY);
        }

        return new self(
            currency: $currency,
            email: $request->input('email', new Missing()),
            comment: $request->input('comment', new Missing()),
            shipping_method_id: $request->input('shipping_method_id', new Missing()),
            digital_shipping_method_id: $request->input('digital_shipping_method_id', new Missing()),
            items: $orderProducts instanceof Missing ? $orderProducts : $items,
            billing_address: $request->has('billing_address')
                ? AddressDto::instantiateFromRequest($request, 'billing_address.') : new Missing(),
            coupons: $request->input('coupons', new Missing()),
            sale_ids: $request->input('sale_ids', new Missing()),
            shipping_number: $request->input('shipping_number', new Missing()),
            shipping_place: is_array($request->input('shipping_place'))
                ? AddressDto::instantiateFromRequest($request, 'shipping_place.')
                : $request->input('shipping_place', new Missing()) ?? new Missing(),
            invoice_requested: $request->input('invoice_requested', new Missing()),
            metadata: self::mapMetadata($request),
            sales_channel_id: $request->input('sales_channel_id'),
            language: app(LanguageService::class)->firstByIdOrDefault(App::getLocale())->iso,
        );
    }

    public function getShippingMethodId(): Missing|string
    {
        return $this->shipping_method_id;
    }

    public function getDigitalShippingMethodId(): Missing|string
    {
        return $this->digital_shipping_method_id;
    }

    public function getItems(): array|Missing
    {
        return $this->items;
    }

    public function getShippingPlace(): AddressDto|Missing|string
    {
        return $this->shipping_place;
    }

    public function getBillingAddress(): AddressDto|Missing
    {
        return $this->billing_address;
    }

    public function getInvoiceRequested(): bool|Missing
    {
        return $this->invoice_requested;
    }

    public function getCoupons(): array|Missing
    {
        return $this->coupons;
    }

    public function getSaleIds(): array|Missing
    {
        return $this->sale_ids;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getProductIds(): array
    {
        if ($this->items instanceof Missing) {
            return [];
        }

        $result = [];
        /** @var OrderProductDto $item */
        foreach ($this->items as $item) {
            $result[] = $item->getProductId();
        }

        return $result;
    }

    public function getCartLength(): float
    {
        if ($this->items instanceof Missing) {
            return 0.0;
        }

        $length = 0.0;
        /** @var OrderProductDto $item */
        foreach ($this->items as $item) {
            $length += $item->getQuantity();
        }

        return $length;
    }
}
