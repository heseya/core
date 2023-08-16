<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Resources;

use App\Http\Resources\CountryResource;
use App\Http\Resources\Resource;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Http\Request;

/**
 * @property SalesChannel $resource
 */
final class SalesChannelResource extends Resource
{
    /**
     * @return array<string, string[]>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'status' => $this->resource->status,
            'countries_block_list' => $this->resource->countries_block_list,
            'default_currency' => $this->resource->default_currency,
            'default_language_id' => $this->resource->default_language_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function view(Request $request): array
    {
        return [
            'country_codes' => $this->resource->countries()->pluck('code'),
        ];
    }
}
