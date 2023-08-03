<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class ProductCreateDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public function __construct(
        readonly public array $translations,
        readonly public array $published,
        readonly public string $slug,
        /** @var PriceDto[] */
        readonly public array $prices_base,
        readonly public bool $public,
        readonly public bool $shipping_digital,
        readonly public Missing|string $id = new Missing(),
        readonly public int|Missing $order = new Missing(),
        readonly public float|Missing $quantity_step = new Missing(),
        readonly public int|Missing|null $google_product_category = new Missing(),
        readonly public float|Missing $vat_rate = new Missing(),
        readonly public float|Missing|null $purchase_limit_per_user = new Missing(),
        readonly public array|Missing $media = new Missing(),
        readonly public array|Missing $tags = new Missing(),
        readonly public array|Missing $schemas = new Missing(),
        readonly public array|Missing $sets = new Missing(),
        readonly public array|Missing $items = new Missing(),
        readonly public Missing|SeoMetadataDto $seo = new Missing(),
        readonly public array|Missing $metadata = new Missing(),
        readonly public array|Missing $attributes = new Missing(),
        readonly public array|Missing $descriptions = new Missing(),
        readonly public array|Missing $relatedSets = new Missing(),
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
            $request->input('prices_base'),
        );

        return new self(
            translations: $request->input('translations', []),
            published: $request->input('published', []),
            slug: $request->input('slug'),
            prices_base: $prices_base,
            public: $request->boolean('public'),
            shipping_digital: $request->boolean('shipping_digital'),
            id: $request->input('id') ?? new Missing(),
            order: $request->input('order') ?? new Missing(),
            quantity_step: $request->input('quantity_step') ?? new Missing(),
            google_product_category: $request->input('google_product_category', new Missing()),
            vat_rate: $request->input('vat_rate') ?? new Missing(),
            purchase_limit_per_user: $request->input('purchase_limit_per_user', new Missing()),
            media: $request->input('media') ?? new Missing(),
            tags: $request->input('tags') ?? new Missing(),
            schemas: $request->input('schemas') ?? new Missing(),
            sets: $request->input('sets') ?? new Missing(),
            items: $request->input('items') ?? new Missing(),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
            metadata: self::mapMetadata($request),
            attributes: $request->input('attributes') ?? new Missing(),
            descriptions: $request->input('descriptions') ?? new Missing(),
            relatedSets: $request->input('related_sets') ?? new Missing(),
        );
    }

    public function getMetadata(): array|Missing
    {
        return $this->metadata;
    }
}
