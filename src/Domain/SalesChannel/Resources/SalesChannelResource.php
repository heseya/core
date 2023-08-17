<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Resources;

use App\Http\Resources\LanguageResource;
use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use Domain\Currency\CurrencyDto;
use Domain\SalesChannel\Models\SalesChannel;
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
            'status' => $this->resource->status,
            'countries_block_list' => $this->resource->countries_block_list,
            'default_currency' => CurrencyDto::from($this->resource->default_currency),
            'default_language' => new LanguageResource($this->resource->defaultLanguage),
            'country_codes' => $this->resource->countries->pluck('code'),
            ...$request->boolean('with_translations') ? $this->getAllTranslations() : [],
        ];
    }
}
