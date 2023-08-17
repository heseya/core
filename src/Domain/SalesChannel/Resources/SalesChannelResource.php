<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Resources;

use App\Http\Resources\LanguageResource;
use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
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
     *
     * @throws UnknownCurrencyException
     */
    public function base(Request $request): array
    {
        $currency = Currency::of($this->resource->default_currency);
        $currencyDto = new CurrencyDto(
            $currency->getName(),
            $currency->getCurrencyCode(),
            $currency->getDefaultFractionDigits(),
        );

        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'status' => $this->resource->status,
            'countries_block_list' => $this->resource->countries_block_list,
            'default_currency' => $currencyDto,
            'default_language' => new LanguageResource($this->resource->defaultLanguage),
            'countries' => $this->resource->countries->pluck('code'),
            ...$request->boolean('with_translations') ? $this->getAllTranslations() : [],
        ];
    }
}
