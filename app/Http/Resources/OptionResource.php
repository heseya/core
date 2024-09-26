<?php

namespace App\Http\Resources;

use App\Models\Option;
use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Domain\Currency\Currency;
use Domain\Price\Dtos\ProductCachedPriceDto;
use Domain\PriceMap\PriceMapService;
use Domain\SalesChannel\SalesChannelService;
use Illuminate\Http\Request;

/**
 * @property Option $resource
 */
class OptionResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    public function base(Request $request): array
    {
        $priceMapService = app(PriceMapService::class);
        $salesChannelService = app(SalesChannelService::class);
        $salesChannel = $salesChannelService->getCurrentRequestSalesChannel();

        $price = ProductCachedPriceDto::from($priceMapService->getOrCreateMappedPriceForPriceMap($this->resource, $salesChannel->priceMap ?? Currency::DEFAULT->getDefaultPriceMapId()), $salesChannel);

        $data = [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'price' => $price,
            'available' => $this->resource->available,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'items' => ItemPublicResource::collection($this->resource->items),
            'default' => $this->resource->default,
        ];

        return array_merge(
            $data,
            $request->boolean('with_translations') ? $this->getAllTranslations('options.show_hidden') : [],
            $this->metadataResource('options.show_metadata_private'),
        );
    }
}
