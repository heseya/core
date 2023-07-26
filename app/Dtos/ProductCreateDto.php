<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
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
        readonly public Missing|string $id,
        readonly public string $slug,
        readonly public float $price,
        readonly public bool $public,
        readonly public bool $shipping_digital,
        readonly public int|Missing $order,
        readonly public float|Missing $quantity_step,
        readonly public int|Missing|null $google_product_category,
        readonly public float|Missing $vat_rate,
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
     */
    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            translations: $request->input('translations', []),
            published: $request->input('published', []),
            id: $request->input('id') ?? new Missing(),
            slug: $request->input('slug'),
            price: $request->input('price'),
            public: $request->boolean('public'),
            shipping_digital: $request->boolean('shipping_digital'),
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
