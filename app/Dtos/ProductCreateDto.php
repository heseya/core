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
        readonly public Missing|string $id,
        readonly public string $name,
        readonly public string $slug,
        /** @var PriceDto[] */
        readonly public array $price_base,
        readonly public bool $public,
        readonly public bool $shipping_digital,
        readonly public int|Missing $order,
        readonly public float|Missing $quantity_step,
        readonly public int|Missing|null $google_product_category,
        readonly public float|Missing $vat_rate,
        readonly public ?string $description_html,
        readonly public ?string $description_short,
        readonly public float|Missing|null $purchase_limit_per_user,
        readonly public array|Missing $media,
        readonly public array|Missing $tags,
        readonly public array|Missing $schemas,
        readonly public array|Missing $sets,
        readonly public array|Missing $items,
        readonly public Missing|SeoMetadataDto $seo,
        readonly public array|Missing $metadata,
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
        $price_base = array_map(
            fn ($data) => new PriceDto(...$data),
            $request->input('price_base'),
        );

        return new self(
            id: $request->input('id') ?? new Missing(),
            name: $request->input('name'),
            slug: $request->input('slug'),
            price_base: $price_base,
            public: $request->boolean('public'),
            shipping_digital: $request->boolean('shipping_digital'),
            order: $request->input('order') ?? new Missing(),
            quantity_step: $request->input('quantity_step') ?? new Missing(),
            google_product_category: $request->input('google_product_category', new Missing()),
            vat_rate: $request->input('vat_rate') ?? new Missing(),
            description_html: $request->input('description_html'),
            description_short: $request->input('description_short'),
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
