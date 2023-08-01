<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Services;

use App\Exceptions\ServerException;
use Domain\Metadata\MetadataService;
use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeDto;
use Domain\ProductAttribute\Dtos\AttributeIndexDto;
use Domain\ProductAttribute\Dtos\AttributeResponseDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Dtos\FiltersDto;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Repositories\AttributeRepository;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;

final readonly class AttributeService
{
    public function __construct(
        private AttributeOptionService $attributeOptionService,
        private MetadataService $metadataService,
        private AttributeRepository $repository,
    ) {}

    /**
     * @param AttributeIndexDto $dto
     *
     * @return PaginatedDataCollection<int, AttributeDto>
     * @throws ServerException
     */
    public function index(AttributeIndexDto $dto): PaginatedDataCollection
    {
        return $this->repository->search($dto);
    }

    public function filters(FiltersDto $dto): DataCollection
    {
        $attributes = $this->repository->getAllGlobal($dto->sets);
        $response = AttributeResponseDto::collection([]);

        foreach ($attributes as $attribute) {
            $response->
        }

        return
    }

    public function show(string $id): AttributeResponseDto
    {
        $attribute = $this->repository->getOne($id);

        [$metadata] = $this->metadataService->getAll(
            Attribute::class,
            [$id],
            Gate::allows('attributes.show_metadata_private'),
        );

        return $this->prepareResponse(
            $attribute,
            $metadata['public'],
            $metadata['private'],
        );
    }

    public function create(AttributeCreateDto $dto): AttributeResponseDto
    {
        $attribute = $this->repository->create($dto);

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync(Attribute::class, $attribute->id, $dto->metadata);
        }

        [$metadata] = $this->metadataService->getAll(
            Attribute::class,
            [$attribute->id],
            Gate::allows('attributes.show_metadata_private'),
        );

        return $this->prepareResponse(
            $attribute,
            $metadata['public'],
            $metadata['private'],
        );
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
        if ($attribute->type === AttributeType::NUMBER) {
            $attribute->refresh();
            $attribute->min_number = $attribute->options->min('value_number');
            $attribute->max_number = $attribute->options->max('value_number');
            $attribute->save();
        } elseif ($attribute->type === AttributeType::DATE) {
            $attribute->refresh();
            $attribute->min_date = $attribute->options->min('value_date');
            $attribute->max_date = $attribute->options->max('value_date');
            $attribute->save();
        }
    }

    /**
     * @param array<string, bool|float|int|string|null> $metadata
     * @param array<string, bool|float|int|string|null> $metadata_private
     */
    private function prepareResponse(AttributeDto $dto, array $metadata, array $metadata_private): AttributeResponseDto
    {
        [$min, $max] = match ($dto->type) {
            AttributeType::NUMBER => [$dto->min_number, $dto->max_number],
            AttributeType::DATE => [$dto->min_date, $dto->max_date],
            default => [null, null],
        };

        return AttributeResponseDto::from($dto, [
            'min' => $min,
            'max' => $max,
            'metadata' => $metadata,
            'metadata_private' => $metadata_private,
        ]);
    }
}
