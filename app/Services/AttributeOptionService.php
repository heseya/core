<?php

namespace App\Services;

use App\Dtos\AttributeOptionDto;
use App\Events\ProductSearchValueEvent;
use App\Models\AttributeOption;
use App\Services\Contracts\AttributeOptionServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use Heseya\Dto\Missing;

readonly class AttributeOptionService implements AttributeOptionServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
    ) {}

    public function create(string $attributeId, AttributeOptionDto $dto): AttributeOption
    {
        $data = array_merge(
            [
                'index' => AttributeOption::withTrashed()->where('attribute_id', '=', $attributeId)->count() + 1,
                'attribute_id' => $attributeId,
            ],
            $dto->toArray(),
        );

        /** @var AttributeOption $attributeOption */
        $attributeOption = AttributeOption::query()->create($data);

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($attributeOption, $dto->getMetadata());
        }

        return $attributeOption;
    }

    public function updateOrCreate(string $attributeId, AttributeOptionDto $dto): AttributeOption
    {
        if ($dto->id !== null && !$dto->id instanceof Missing) {
            /** @var AttributeOption $attributeOption */
            $attributeOption = AttributeOption::query()->findOrFail($dto->id);
            $attributeOption->update($dto->toArray());

            ProductSearchValueEvent::dispatch($attributeOption->productAttributes->pluck('product_id')->toArray());

            return $attributeOption;
        }

        return $this->create($attributeId, $dto);
    }

    public function delete(AttributeOption $attributeOption): void
    {
        $productIds = $attributeOption->productAttributes->pluck('product_id')->toArray();
        $attributeOption->delete();
        ProductSearchValueEvent::dispatch($productIds);
    }

    public function deleteAll(string $attributeId): void
    {
        AttributeOption::query()
            ->where('attribute_id', '=', $attributeId)
            ->delete();
    }
}
