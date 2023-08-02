<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Repositories;

use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Models\Attribute;

final readonly class AttributeRepository
{
    public function getOne(string $id): AttributeDto
    {
        return AttributeDto::from(
            Attribute::query()->findOrFail($id),
        );
    }

    public function create(AttributeCreateDto $dto): AttributeDto
    {
        return AttributeDto::from(
            Attribute::query()->create($dto->toArray()),
        );
    }

    public function update(string $id, AttributeUpdateDto $dto): void
    {
        Attribute::query()
            ->where('id', '=', $id)
            ->update($dto->toArray());
    }

    public function delete(string $id): void
    {
        Attribute::query()->where('id', '=', $id)->delete();
    }
}
