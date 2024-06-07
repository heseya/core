<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use App\Traits\ModifyLangFallback;
use Domain\ProductAttribute\Models\AttributeOption;
use Illuminate\Http\Request;

/**
 * @property AttributeOption $resource
 */
final class OrderAttributeOptionResource extends Resource
{
    use ModifyLangFallback;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'value_number' => $this->resource->value_number,
            'value_date' => $this->resource->value_date,
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
