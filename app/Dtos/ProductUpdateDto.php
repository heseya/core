<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateDto extends Dto implements InstantiateFromRequest
{
    public string|Missing $name;
    public string|Missing $slug;
    public float|Missing $price;
    public bool|Missing $public;
    public bool|Missing $shipping_digital;

    public int|Missing $order;
    public float|Missing $quantity_step;
    public int|null|Missing $google_product_category;
    public float|Missing $vat_rate;

    public string|null|Missing $description_html;
    public string|null|Missing $description_short;
    public float|null|Missing $purchase_limit_per_user;

    public array|Missing $media;
    public array|Missing $tags;
    public array|Missing $schemas;
    public array|Missing $sets;
    public array|Missing $items;
    public SeoMetadataDto|Missing $seo;
    public array|Missing $attributes;
    public array|Missing $descriptions;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            slug: $request->input('slug', new Missing()),
            price: $request->input('price', new Missing()),
            public: $request->input('public', new Missing()),
            shipping_digital: $request->input('shipping_digital', new Missing()),
            order: $request->input('order', new Missing()),
            quantity_step: $request->input('quantity_step', new Missing()),
            google_product_category: $request->input('google_product_category', new Missing()),
            vat_rate: $request->input('vat_rate', new Missing()),
            description_html: $request->input('description_html', new Missing()),
            description_short: $request->input('description_short', new Missing()),
            purchase_limit_per_user: $request->input('purchase_limit_per_user', new Missing()),
            media: $request->input('media', new Missing()),
            tags: $request->input('tags', new Missing()),
            schemas: $request->input('schemas', new Missing()),
            sets: $request->input('sets', new Missing()),
            items: $request->input('items', new Missing()),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
            attributes: $request->input('attributes', new Missing()),
            descriptions: $request->input('descriptions', new Missing()),
        );
    }

    public function getMedia(): Missing|array
    {
        return $this->media;
    }

    public function getTags(): Missing|array
    {
        return $this->tags;
    }

    public function getSchemas(): Missing|array
    {
        return $this->schemas;
    }

    public function getSets(): Missing|array
    {
        return $this->sets;
    }

    public function getItems(): Missing|array
    {
        return $this->items;
    }

    public function getSeo(): SeoMetadataDto|Missing
    {
        return $this->seo;
    }

    public function getMetadata(): Missing
    {
        return new Missing();
    }

    public function getAttributes(): Missing|array
    {
        return $this->attributes;
    }

    public function getPurchaseLimitPerUser(): float|Missing|null
    {
        return $this->purchase_limit_per_user;
    }

    public function getDescriptions(): Missing|array
    {
        return $this->descriptions;
    }
}
