<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use App\Models\ProductAttribute;
use App\Traits\ModifyLangFallback;
use Illuminate\Http\Request;

/**
 * @property ProductAttribute $resource
 */
final class OrderProductAttributeResource extends Resource
{
    use ModifyLangFallback;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->attribute?->id,
            'name' => $this->resource->attribute?->name,
            'slug' => $this->resource->attribute?->slug,
            'selected_options' => OrderAttributeOptionResource::collection(
                $this->resource->options ?? $this->resource->attribute?->options ?? [],
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $previousSettings = $this->getCurrentLangFallbackSettings();
        $this->setAnyLangFallback();
        $result = parent::toArray($request);
        $this->setLangFallbackSettings(...$previousSettings);

        return $result;
    }
}
