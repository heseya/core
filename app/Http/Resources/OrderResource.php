<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Traits\MetadataResource;
use Domain\Order\Resources\OrderStatusResource;
use Domain\SalesChannel\Resources\SalesChannelResource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
        $orderDiscounts = OrderDiscountResource::collection($this->resource->discounts);
        $productsDiscounts = $this->resource->products->map(fn ($product) => OrderDiscountResource::collection($product->discounts));
        $productsDiscountMerged = new Collection();

        $productsDiscounts->each(function ($productDiscounts) use ($productsDiscountMerged): void {
            $productDiscounts->each(function ($discount) use ($productsDiscountMerged): void {
                $found = $productsDiscountMerged
                    ->where('code', '=', $discount->code)
                    ->where('name', '=', $discount->name);

                if ($found->isEmpty()) {
                    $productsDiscountMerged->push($discount);
                } else {
                    // @phpstan-ignore-next-line
                    $found->first()->pivot->applied_discount += $discount->pivot->applied_discount;
                }
            });
        });

        $productsDiscountMerged->map(function ($discount) use (&$orderDiscounts) {
            return $orderDiscounts = $orderDiscounts->push($discount);
        });

        return [
            'products' => OrderProductResource::collection($this->resource->products),
            'payments' => PaymentResource::collection($this->resource->payments),
            'shipping_number' => $this->resource->shipping_number,
            'discounts' => $orderDiscounts,
            'buyer' => $this->resource->buyer instanceof User
                ? UserResource::make($this->resource->buyer)->baseOnly()
                : AppResource::make($this->resource->buyer),
        ];
    }
}
