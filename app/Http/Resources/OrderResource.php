<?php

namespace App\Http\Resources;

use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\User;
use App\Traits\MetadataResource;
use Domain\Order\Resources\OrderStatusResource;
use Domain\SalesChannel\Resources\SalesChannelResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property Order $resource
 */
class OrderResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'code' => $this->resource->code,
            'email' => $this->resource->email,
            'payable' => $this->resource->payable,
            'currency' => $this->resource->currency,
            'summary' => PriceResource::make($this->resource->summary),
            'summary_paid' => PriceResource::make($this->resource->paid_amount),
            'shipping_price_initial' => PriceResource::make($this->resource->shipping_price_initial),
            'shipping_price' => PriceResource::make($this->resource->shipping_price),
            'comment' => $this->resource->comment,
            'status' => $this->resource->status ? OrderStatusResource::make($this->resource->status) : null,
            'shipping_method' => $this->resource->shippingMethod ?
                ShippingMethodResource::make($this->resource->shippingMethod) : null,
            'digital_shipping_method' => $this->resource->digitalShippingMethod ?
                ShippingMethodResource::make($this->resource->digitalShippingMethod) : null,
            'shipping_type' => $this->resource->shippingType,
            'invoice_requested' => $this->resource->invoice_requested,
            'billing_address' => AddressResource::make($this->resource->invoiceAddress),
            'shipping_place' => $this->resource->shippingAddress
                ? AddressResource::make($this->resource->shippingAddress)
                : $this->resource->shipping_place,
            'documents' => OrderDocumentResource::collection($this->resource->documents->pluck('pivot')),
            'paid' => $this->resource->paid,
            'cart_total' => PriceResource::make($this->resource->cart_total),
            'cart_total_initial' => PriceResource::make($this->resource->cart_total_initial),
            'created_at' => $this->resource->created_at,
            'sales_channel' => SalesChannelResource::make($this->resource->salesChannel),
        ], $this->metadataResource('orders.show_metadata_private'));
    }

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
                    $found->first()->pivot->applied_discount += $discount->pivot->applied_discount;
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
        ];
    }
}
