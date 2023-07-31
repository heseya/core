<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Repositories;

use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeDto;
use Domain\ProductAttribute\Dtos\AttributeIndexDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Models\Attribute;
use Illuminate\Support\Facades\Config;

final readonly class AttributeRepository
{
    /**
     * @return AttributeDto[]
     */
    public function index(AttributeIndexDto $dto): array
    {
        $query = Attribute::searchByCriteria($dto->toArray())
            ->orderBy('order')
            ->with(['metadata', 'metadataPrivate']);

        return array_map(function ($el) {
            return AttributeDto::from($el);
        }, $query->paginate(Config::get('pagination.per_page')));
    }

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
