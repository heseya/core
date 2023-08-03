<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Repositories;

use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Models\Attribute;

final readonly class AttributeRepository
{
    public function getOne(string $id): Attribute
    {
        return Attribute::query()->findOrFail($id);
    }

    public function create(AttributeCreateDto $dto): Attribute
    {
        return Attribute::query()->create($dto->toArray());
    }

    /**
     * Update given model, returns number of rows affected.
     */
    public function update(string $id, AttributeUpdateDto $dto): int
    {
        return Attribute::query()
            ->where('id', '=', $id)
            ->update($dto->toArray());
    }

    public function delete(string $id): void
    {
        Attribute::query()->where('id', '=', $id)->delete();
    }
}
