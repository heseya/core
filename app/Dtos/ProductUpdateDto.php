<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateDto extends Dto implements InstantiateFromRequest
{
    public function __construct(
        readonly public Missing|string $name,
        readonly public Missing|string $slug,
        readonly public float|Missing $price,
        readonly public bool|Missing $public,
        readonly public bool|Missing $shipping_digital,
        readonly public float|Missing $quantity_step,
        readonly public int|Missing|null $google_product_category,
        readonly public float|Missing $vat_rate,
        readonly public Missing|string|null $description_html,
        readonly public Missing|string|null $description_short,
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
     */
    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name') ?? new Missing(),
            slug: $request->input('slug') ?? new Missing(),
            price: $request->input('price') ?? new Missing(),
            public: $request->input('public') ?? new Missing(),
            shipping_digital: $request->input('shipping_digital') ?? new Missing(),
            quantity_step: $request->input('quantity_step') ?? new Missing(),
            google_product_category: $request->input('google_product_category', new Missing()),
            vat_rate: $request->input('vat_rate') ?? new Missing(),
            description_html: $request->input('description_html', new Missing()),
            description_short: $request->input('description_short', new Missing()),
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

    public static function fromArray(array $data): self
    {
        return new self(
            name: array_key_exists('name', $data) ? $data['name'] : new Missing(),
            slug: array_key_exists('slug', $data) ? $data['slug'] : new Missing(),
            price: array_key_exists('price', $data) ? $data['price'] : new Missing(),
            public: array_key_exists('public', $data) ? $data['public'] : new Missing(),
            shipping_digital: array_key_exists('shipping_digital', $data) ? $data['shipping_digital'] : new Missing(),
            quantity_step: array_key_exists('quantity_step', $data) ? $data['quantity_step'] : new Missing(),
            google_product_category: array_key_exists('google_product_category', $data) ? $data['google_product_category'] : new Missing(),
            vat_rate: array_key_exists('vat_rate', $data) ? $data['vat_rate'] : new Missing(),
            description_html: array_key_exists('description_html', $data) ? $data['description_html'] : new Missing(),
            description_short: array_key_exists('description_short', $data) ? $data['description_short'] : new Missing(),
            purchase_limit_per_user: array_key_exists('purchase_limit_per_user', $data) ? $data['purchase_limit_per_user'] : new Missing(),
            media: array_key_exists('media', $data) ? $data['media'] : new Missing(),
            tags: array_key_exists('tags', $data) ? $data['tags'] : new Missing(),
            schemas: array_key_exists('schemas', $data) ? $data['schemas'] : new Missing(),
            sets: array_key_exists('sets', $data) ? $data['sets'] : new Missing(),
            items: array_key_exists('items', $data) ? $data['items'] : new Missing(),
            seo: array_key_exists('seo', $data) ? $data['seo'] : new Missing(),
            attributes: array_key_exists('attributes', $data) ? $data['attributes'] : new Missing(),
            descriptions: array_key_exists('descriptions', $data) ? $data['descriptions'] : new Missing(),
            relatedSets: array_key_exists('relatedSets', $data) ? $data['relatedSets'] : new Missing(),
        );
    }

    public function getMetadata(): Missing
    {
        return new Missing();
    }
}
