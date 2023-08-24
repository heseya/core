<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\Product;
use Domain\Metadata\MetadataService;
use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Repositories\AttributeRepository;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Optional;

final readonly class AttributeService
{
    public function __construct(
        private AttributeOptionService $attributeOptionService,
        private MetadataService $metadataService,
        private AttributeRepository $repository,
    ) {}

    public function show(string $id): Attribute
    {
        return $this->repository->getOne($id);
    }

    public function create(AttributeCreateDto $dto): Attribute
    {
        $attribute = $this->repository->create($dto);

        if (!($dto->metadata_computed instanceof Optional)) {
            $this->metadataService->sync($attribute->getMorphClass(), $attribute->getKey(), $dto->metadata_computed);
        }

        return $attribute;
    }

    /**
     * @throws ClientException
     */
    public function update(string $id, AttributeUpdateDto $dto): void
    {
        if (!$this->repository->update($id, $dto)) {
            throw new ClientException(Exceptions::CLIENT_CANNOT_DELETE_MODEL);
        }
    }

    public function delete(string $id): void
    {
        $this->attributeOptionService->deleteAll($id);
        $this->repository->delete($id);
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
