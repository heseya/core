<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Resources;

use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use App\Traits\ModifyLangFallback;
use Illuminate\Http\Request;

final class AttributeOptionResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;
    use ModifyLangFallback;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'index' => $this->resource->index,
            'value_number' => $this->resource->value_number,
            'value_date' => $this->resource->value_date,
            'attribute_id' => $this->resource->attribute_id,
            ...$request->boolean('with_translations') ? $this->getAllTranslations('attributes.show_hidden') : [],
        ], $this->metadataResource('attributes.show_metadata_private'));
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
