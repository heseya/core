<?php

namespace App\Services;

use App\Dtos\AttributeOptionDto;
use App\Models\AttributeOption;
use App\Services\Contracts\AttributeOptionServiceContract;

class AttributeOptionService implements AttributeOptionServiceContract
{
    public function create(string $attributeId, AttributeOptionDto $dto): AttributeOption
    {
        $data = array_merge(
            ['attribute_id' => $attributeId],
            $dto->toArray(),
        );

        return AttributeOption::create($data);
    }

    public function updateOrCreate(string $attributeId, AttributeOptionDto $dto): AttributeOption
    {
        if ($dto->getId() !== null) {
            $attributeOption = AttributeOption::findOrFail($dto->getId());
            $attributeOption->update($dto->toArray());

            return $attributeOption;
        }

        return $this->create($attributeId, $dto);
    }

    public function deleteAttributeOptions(string $attributeId): void
    {
        AttributeOption::query()
            ->where('attribute_id', '=', $attributeId)
            ->delete();
    }
}
