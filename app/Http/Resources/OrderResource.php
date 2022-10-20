<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Traits\MetadataResource;
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
            'summary' => $this->resource->summary,
            'summary_paid' => $this->resource->paid_amount,
            'shipping_price_initial' => $this->resource->shipping_price_initial,
            'shipping_price' => $this->resource->shipping_price,
            'comment' => $this->resource->comment,
            'status' => $this->resource->status ? StatusResource::make($this->resource->status) : null,
            'shipping_method_id' => $this->resource->shippingMethod ? $this->resource->shippingMethod->getKey() : null,
            'shipping_type' => $this->resource->shippingMethod ? $this->resource->shippingMethod->shipping_type : null,
            'invoice_requested' => $this->resource->invoice_requested,
            'shipping_place' => AddressResource::make($this->resource->shippingAddress) ?? $this->resource->shipping_place,
            'documents' => OrderDocumentResource::collection($this->resource->documents->pluck('pivot')),
            'paid' => $this->resource->paid,
            'cart_total' => $this->resource->cart_total,
            'cart_total_initial' => $this->resource->cart_total_initial,
            'delivery_address' => $this->resource->deliveryAddress ?
                AddressResource::make($this->resource->deliveryAddress) : null,
            'created_at' => $this->resource->created_at,
        ], $this->metadataResource('orders.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        $orderDiscounts = OrderDiscountResource::collection($this->resource->discounts);
        $productsDiscounts = $this->resource->products->map(function ($product) {
            return OrderDiscountResource::collection($product->discounts);
        });
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
            'billing_address' => AddressResource::make($this->resource->invoiceAddress),
            'products' => OrderProductResource::collection($this->resource->products),
            'payments' => PaymentResource::collection($this->resource->payments),
            'shipping_number' => $this->resource->shipping_number,
            'discounts' => $orderDiscounts,
            'buyer' => $this->resource->buyer instanceof User
                ? UserResource::make($this->resource->buyer)->baseOnly() : AppResource::make($this->resource->buyer),
        ];
    }
}
