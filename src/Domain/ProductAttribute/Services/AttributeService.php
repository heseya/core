<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Services;

use Domain\Metadata\MetadataService;
use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeDto;
use Domain\ProductAttribute\Dtos\AttributeIndexDto;
use Domain\ProductAttribute\Dtos\AttributeResponseDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Repositories\AttributeRepository;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Optional;

final readonly class AttributeService
{
    public function __construct(
        private AttributeOptionService $attributeOptionService,
        private MetadataService $metadataService,
        private AttributeRepository $repository,
    ) {}

    /**
     * @return AttributeDto[]
     */
    public function index(AttributeIndexDto $dto): array
    {
        return $this->repository->index($dto);
    }

    public function show(string $id): AttributeResponseDto
    {
        $attribute = $this->repository->getOne($id);

        if (Gate::allows('attributes.show_metadata_private')) {
            $metadata_private = [];
        }

        [$min, $max] = match ($attribute->type) {
            AttributeType::NUMBER => [$attribute->min_number, $attribute->max_number],
            AttributeType::DATE => [$attribute->min_date, $attribute->max_date],
            default => [null, null],
        };

        return AttributeResponseDto::from([
            ...$attribute->toArray(),
            'min' => $min,
            'max' => $max,
            'metadata' => [],
            'metadata_private' => $metadata_private,
        ]);
    }

    public function create(AttributeCreateDto $dto): AttributeResponseDto
    {
        $attribute = $this->repository->create($dto);

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync(Attribute::class, $attribute->id, $dto->metadata);
        }

        return $this->show($attribute->id);
    }

    public function update(string $id, AttributeUpdateDto $dto): AttributeResponseDto
    {
        $this->repository->update($id, $dto);

        return $this->show($id);
    }

    public function delete(string $id): void
    {
        $this->attributeOptionService->deleteAll($id);
        $this->repository->delete($id);
    }

    public function updateMinMax(Attribute $attribute): void
    {
        $attribute->refresh();

        if ($attribute->type === AttributeType::NUMBER) {
            $attribute->min_number = $attribute->options->min('value_number');
            $attribute->max_number = $attribute->options->max('value_number');
            $attribute->save();
        } elseif ($attribute->type === AttributeType::DATE) {
            $attribute->min_date = $attribute->options->min('value_date');
            $attribute->max_date = $attribute->options->max('value_date');
            $attribute->save();
        }
    }
}
