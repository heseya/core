<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Resources;

use App\Http\Resources\LanguageResource;
use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use Domain\PaymentMethods\Resources\PaymentMethodResource;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Resources\ShippingMethodResource;
use Illuminate\Http\Request;

/**
 * @property SalesChannel $resource
 */
final class SalesChannelResource extends Resource
{
    use GetAllTranslations;

    /**
     * @return array<string, string[]>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'vat_rate' => $this->resource->vat_rate,
            'status' => $this->resource->status,
            'activity' => $this->resource->activity,
            'language' => LanguageResource::make($this->resource->language),
            'default' => $this->resource->default,
            'published' => $this->resource->published,
            ...$request->boolean('with_translations') ? $this->getAllTranslations('sales_channels.show_hidden') : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function view(Request $request): array
    {
        return [
            // TODO price_map
            //            'price_map' => '',
            'shipping_methods' => ShippingMethodResource::collection($this->resource->shippingMethods),
            'payment_methods' => PaymentMethodResource::collection($this->resource->paymentMethods),
            'organization_count' => $this->resource->organizations_count,
        ];
    }
}
