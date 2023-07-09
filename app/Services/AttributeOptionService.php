<?php

namespace App\Services;

use App\Dtos\AttributeOptionDto;
use App\Models\AttributeOption;
use App\Services\Contracts\AttributeOptionServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use Heseya\Dto\Missing;

class AttributeOptionService implements AttributeOptionServiceContract
{
    public function __construct(private MetadataServiceContract $metadataService) {}

    public function create(string $attributeId, AttributeOptionDto $dto): AttributeOption
    {
        $data = array_merge(
            [
                'index' => AttributeOption::withTrashed()->where('attribute_id', '=', $attributeId)->count() + 1,
                'attribute_id' => $attributeId,
            ],
            $dto->toArray(),
        );
        $attributeOption = AttributeOption::create($data);

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($attributeOption, $dto->getMetadata());
        }

        return $attributeOption;
    }

    public function updateOrCreate(string $attributeId, AttributeOptionDto $dto): AttributeOption
    {
        if ($dto->id !== null && !$dto->id instanceof Missing) {
            $attributeOption = AttributeOption::findOrFail($dto->id);
            $attributeOption->update($dto->toArray());

            return $attributeOption;
        }

        return $this->create($attributeId, $dto);
    }

    public function delete(AttributeOption $attributeOption): void
    {
        $attributeOption->delete();
    }

    public function deleteAll(string $attributeId): void
    {
        AttributeOption::query()
            ->where('attribute_id', '=', $attributeId)
            ->delete();
    }
}
