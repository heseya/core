<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Services;

use App\Services\Contracts\MetadataServiceContract;
use Domain\ProductAttribute\Dtos\AttributeOptionDto;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductAttribute\Repositories\AttributeOptionRepository;
use Spatie\LaravelData\Optional;

final readonly class AttributeOptionService
{
    public function __construct(
        private MetadataServiceContract $metadataService,
        private AttributeOptionRepository $attributeOptionRepository,
    ) {}

    public function create(AttributeOptionDto $dto): AttributeOption
    {
        $attributeOption = $this->attributeOptionRepository->create($dto);

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync($attributeOption, $dto->metadata);
        }

        return $attributeOption;
    }

    public function updateOrCreate(AttributeOptionDto $dto): AttributeOption
    {
        if ($dto->id !== null && !$dto->id instanceof Optional) {
            $attributeOption = $this->attributeOptionRepository->update($dto->id, $dto);
        } else {
            $attributeOption = $this->create($dto);
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
