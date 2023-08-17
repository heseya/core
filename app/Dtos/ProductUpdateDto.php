<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateDto extends Dto implements InstantiateFromRequest
{
    public function __construct(
        readonly public array $translations,
        readonly public array $published,
        readonly public Missing|string $slug,
        /** @var PriceDto[] */
        readonly public array $prices_base,
        readonly public bool|Missing $public,
        readonly public bool|Missing $shipping_digital,
        readonly public int|Missing $order,
        readonly public float|Missing $quantity_step,
        readonly public int|Missing|null $google_product_category,
        readonly public float|Missing|null $purchase_limit_per_user,
        readonly public array|Missing $media,
        readonly public array|Missing $tags,
        readonly public array|Missing $schemas,
        readonly public array|Missing $sets,
        readonly public array|Missing $items,
        readonly public Missing|SeoMetadataDto $seo,
        readonly public array|Missing $attributes,
        readonly public array|Missing $descriptions,
        readonly public array|Missing $relatedSets,
    ) {}

    /**
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public static function instantiateFromRequest(FormRequest $request): self
    {
        $prices_base = array_map(
            fn ($data) => PriceDto::fromData(...$data),
            $request->input('prices_base') ?? [],
        );

        return new self(
            translations: $request->input('translations', []),
            published: $request->input('published', []),
            slug: $request->input('slug') ?? new Missing(),
            prices_base: $prices_base,
            public: $request->input('public') ?? new Missing(),
            shipping_digital: $request->input('shipping_digital') ?? new Missing(),
            order: $request->input('order') ?? new Missing(),
            quantity_step: $request->input('quantity_step') ?? new Missing(),
            google_product_category: $request->input('google_product_category', new Missing()),
            purchase_limit_per_user: $request->input('purchase_limit_per_user', new Missing()),
            media: $request->input('media') ?? new Missing(),
            tags: $request->input('tags') ?? new Missing(),
            schemas: $request->input('schemas') ?? new Missing(),
            sets: $request->input('sets') ?? new Missing(),
            items: $request->input('items') ?? new Missing(),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
            attributes: $request->input('attributes') ?? new Missing(),
            descriptions: $request->input('descriptions') ?? new Missing(),
            relatedSets: $request->input('related_sets') ?? new Missing(),
        );
    }

    public function getMetadata(): Missing
    {
        return new Missing();
    }
}
