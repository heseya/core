<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Services;

use App\Models\Product;
use Domain\Metadata\MetadataService;
use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeDto;
use Domain\ProductAttribute\Dtos\AttributeResponseDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Repositories\AttributeRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Optional;

final readonly class AttributeService
{
    public function __construct(
        private AttributeOptionService $attributeOptionService,
        private MetadataService $metadataService,
        private AttributeRepository $repository,
    ) {}

    public function show(string $id): AttributeResponseDto
    {
        $attribute = $this->repository->getOne($id);

        [$metadata] = $this->metadataService->getAll(
            [$id],
            Gate::allows('attributes.show_metadata_private'),
        );

        return $this->prepareResponse($attribute, $metadata);
    }

    public function create(AttributeCreateDto $dto): AttributeResponseDto
    {
        $attribute = $this->repository->create($dto);

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync(Attribute::class, $attribute->id, $dto->metadata);
        }

        [$metadata] = $this->metadataService->getAll(
            [$attribute->id],
            Gate::allows('attributes.show_metadata_private'),
        );

        return $this->prepareResponse($attribute, $metadata);
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

    /**
     * @param array<string, bool|float|int|string|null>[] $metadata
     */
    private function prepareResponse(AttributeDto $dto, array $metadata): AttributeResponseDto
    {
        [$min, $max] = match ($dto->type) {
            AttributeType::NUMBER => [$dto->min_number, $dto->max_number],
            AttributeType::DATE => [$dto->min_date, $dto->max_date],
            default => [null, null],
        };

        return AttributeResponseDto::from($dto, [
            'min' => $min,
            'max' => $max,
            'metadata' => $metadata['public'],
            'metadata_private' => $metadata['private'],
        ]);
    }

    // TODO: refactor this
    /**
     * @param array<string, mixed> $data
     */
    public function sync(Product $product, array $data): void
    {
        $attributes = Arr::divide($data)[0];

        $product->attributes()->sync($attributes);
        $product->attributes()->get()->each(
            fn (Attribute $attribute) => $attribute->pivot->options()->sync($data[$attribute->getKey()])
        );
    }
}
