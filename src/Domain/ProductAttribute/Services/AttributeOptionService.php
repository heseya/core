<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Services;

use App\Dtos\AttributeOptionDto;
use App\Services\Contracts\MetadataServiceContract;
use Domain\ProductAttribute\Models\AttributeOption;
use Heseya\Dto\Missing;

final readonly class AttributeOptionService
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
        } else {
            $attributeOption = $this->create($attributeId, $dto);
        }

        return $attributeOption;
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
