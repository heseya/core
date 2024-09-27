<?php

namespace App\Http\Resources;

use App\Models\Product;
use Domain\Currency\Currency;
use Domain\Price\Dtos\ProductCachedPriceDto;
use Domain\PriceMap\PriceMapService;
use Domain\SalesChannel\SalesChannelService;
use Illuminate\Http\Request;

/**
 * @property Product $resource
 */
class ProductShortResource extends Resource
{
    public function base(Request $request): array
    {
        $priceMapService = app(PriceMapService::class);
        $salesChannelService = app(SalesChannelService::class);
        $salesChannel = $salesChannelService->getCurrentRequestSalesChannel();

        $initial_price = $this->resource->getCachedInitialPriceForSalesChannel($salesChannel);
        $price = $this->resource->getCachedMinPriceForSalesChannel($salesChannel);

        if ($initial_price === null) {
            $initial_price = ProductCachedPriceDto::from($priceMapService->getOrCreateMappedPriceForPriceMap($this->resource, $salesChannel->priceMap ?? Currency::DEFAULT->getDefaultPriceMapId()), $salesChannel);
        } else {
            $initial_price = ProductCachedPriceDto::from($initial_price);
        }
        if ($price === null) {
            $price = $initial_price;
        } else {
            $price = ProductCachedPriceDto::from($price);
        }

        return [
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'price_initial' => $initial_price,
            'price' => $price,
            'public' => $this->resource->public,
            'visible' => $this->resource->public,
            'available' => $this->resource->available,
            'cover' => MediaResource::make($this->resource->media->first()),
            'quantity' => $this->resource->quantity,
        ];
    }
}
