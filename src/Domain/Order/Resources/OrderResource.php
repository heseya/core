<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\AddressResource;
use App\Http\Resources\AppResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\Resource;
use App\Http\Resources\UserResource;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderProduct;
use App\Models\User;
use App\Traits\MetadataResource;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Domain\Order\Dtos\OrderPriceDto;
use Domain\Organization\Resources\OrganizationResource;
use Domain\PaymentMethods\Resources\PaymentMethodResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property Order $resource
 */
final class OrderResource extends Resource
{
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'code' => $this->resource->code,
            'email' => $this->resource->email,
            'payable' => $this->resource->payable,
            'currency' => $this->resource->currency,
            'summary' => OrderPriceDto::from($this->resource->summary, $this->resource->vat_rate, false),
            'summary_paid' => OrderPriceDto::from($this->resource->paid_amount, $this->resource->vat_rate, false),
            'shipping_price_initial' => OrderPriceDto::from($this->resource->shipping_price_initial, $this->resource->vat_rate, false),
            'shipping_price' => OrderPriceDto::from($this->resource->shipping_price, $this->resource->vat_rate, false),
            'comment' => $this->resource->comment,
            'status' => $this->resource->status ? OrderStatusResource::make($this->resource->status) : null,
            'shipping_method' => $this->resource->shippingMethod ?
                OrderShippingMethodResource::make($this->resource->shippingMethod) : null,
            'digital_shipping_method' => $this->resource->digitalShippingMethod ?
                OrderShippingMethodResource::make($this->resource->digitalShippingMethod) : null,
            'shipping_type' => $this->resource->shippingType,
            'invoice_requested' => $this->resource->invoice_requested,
            'billing_address' => AddressResource::make($this->resource->invoiceAddress),
            'shipping_place' => $this->resource->shippingAddress
                ? AddressResource::make($this->resource->shippingAddress)
                : $this->resource->shipping_place,
            'documents' => OrderDocumentResource::collection($this->resource->documents->pluck('pivot')),
            'paid' => $this->resource->paid,
            'cart_total' => OrderPriceDto::from($this->resource->cart_total, $this->resource->vat_rate, false),
            'cart_total_initial' => OrderPriceDto::from($this->resource->cart_total_initial, $this->resource->vat_rate, false),
            'created_at' => $this->resource->created_at,
            'sales_channel' => OrderSalesChannelResource::make($this->resource->salesChannel),
            'language' => $this->resource->language,
            'payment_method' => $this->resource->payment_method ? PaymentMethodResource::make($this->resource->payment_method) : null,
            'payment_method_type' => $this->resource->payment_method_type,
        ], $this->metadataResource('orders.show_metadata_private'));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function view(Request $request): array
    {
        $discounts = $this->resource->discounts;

        $productsDiscounts = $this->resource->products->reduce(function (Collection $carry, OrderProduct $product): Collection {
            $product->discounts->each(function (Discount $discount) use (&$carry): void {
                $found = $carry
                    ->where('code', '=', $discount->code)
                    ->where('name', '=', $discount->name);

                if ($found->isEmpty()) {
                    $carry->push($discount);
                } else {
                    $foundDiscount = $found->first();
                    assert($foundDiscount instanceof Discount);
                    assert($foundDiscount->order_discount instanceof OrderDiscount);
                    $foundDiscount->order_discount->applied = match (true) {
                        $foundDiscount->order_discount->applied !== null && $discount->order_discount?->applied !== null => $foundDiscount->order_discount->applied->plus($discount->order_discount->applied),
                        $discount->order_discount?->applied !== null => $discount->order_discount->applied,
                        default => $foundDiscount->order_discount->applied,
                    };
                }
            });

            return $carry;
        }, new Collection());

        $productsDiscounts->each(function ($discount) use (&$discounts): void {
            $discounts->push($discount);
        });

        return [
            'products' => OrderProductResource::collection($this->resource->products),
            'payments' => PaymentResource::collection($this->resource->payments),
            'shipping_number' => $this->resource->shipping_number,
            'discounts' => OrderDiscountResource::collection($discounts),
            'buyer' => $this->resource->buyer instanceof User
                ? UserResource::make($this->resource->buyer)->baseOnly()
                : AppResource::make($this->resource->buyer),
            'organization' => OrganizationResource::make($this->resource->organization),
        ];
    }
}
