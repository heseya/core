<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

final class OrderStatusResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'color' => $this->resource->color,
            'cancel' => $this->resource->cancel,
            'description' => $this->resource->description,
            'hidden' => $this->resource->hidden,
            'no_notifications' => $this->resource->no_notifications,
            ...$this->metadataResource('statuses.show_metadata_private'),
            ...$request->boolean('with_translations') ? $this->getAllTranslations() : [],
        ];
    }
}
