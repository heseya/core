<?php

namespace App\Http\Resources;

use App\Enums\VisibilityType;
use App\Models\MediaAttachment;
use App\Models\Product;
use App\Models\ProductSet;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @property Product $resource
 */
class ProductResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'price' => $this->resource->price,
            'price_min' => $this->resource->price_min ?? $this->resource->price_min_initial,
            'price_max' => $this->resource->price_max ?? $this->resource->price_max_initial,
            'price_min_initial' => $this->resource->price_min_initial,
            'price_max_initial' => $this->resource->price_max_initial,
            'public' => $this->resource->public,
            'visible' => $this->resource->public,
            'available' => $this->resource->available,
            'quantity_step' => $this->resource->quantity_step,
            'google_product_category' => $this->resource->google_product_category,
            'vat_rate' => $this->resource->vat_rate,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'cover' => MediaResource::make($this->resource->media->first()),
            'tags' => TagResource::collection($this->resource->tags),
            'has_schemas' => (bool) $this->resource->has_schemas,
            'quantity' => $this->resource->quantity,
            'shipping_digital' => $this->resource->shipping_digital,
            'purchase_limit_per_user' => $this->resource->purchase_limit_per_user,
        ], $this->metadataResource('products.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        $sets = Gate::denies('product_sets.show_hidden')
            ? $this->resource->sets->filter(
                fn (ProductSet $set) => $set->public === true && $set->public_parent === true,
            )
            : $this->resource->sets;

        $relatedSets = Gate::denies('product_sets.show_hidden')
            ? $this->resource->relatedSets->filter(
                fn (ProductSet $set) => $set->public === true && $set->public_parent === true,
            )
            : $this->resource->relatedSets;

        $attachments = Gate::denies('products.show_attachments_private')
            ? $this->resource->attachments->filter(
                fn (MediaAttachment $attachment) => $attachment->visibility === VisibilityType::PUBLIC,
            )
            : $this->resource->attachments;

        return [
            'description_html' => $this->resource->description_html,
            'description_short' => $this->resource->description_short,
            'descriptions' => PageResource::collection($this->resource->pages),
            'items' => ProductItemResource::collection($this->resource->items),
            'gallery' => MediaResource::collection($this->resource->media),
            'schemas' => SchemaResource::collection($this->resource->schemas),
            'sets' => ProductSetResource::collection($sets),
            'related_sets' => ProductSetResource::collection($relatedSets),
            'attributes' => ProductAttributeResource::collection($this->resource->attributes),
            'seo' => SeoMetadataResource::make($this->resource->seo),
            'sales' => SaleResource::collection($this->resource->sales),
            'attachments' => MediaAttachmentResource::collection($attachments),
        ];
    }

    public function index(Request $request): array
    {
        return [
            'attributes' => ProductAttributeShortResource::collection($this->resource->attributes),
        ];
    }
}
