<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Repositories;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeDto;
use Domain\ProductAttribute\Dtos\AttributeIndexDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Models\Attribute;
use Illuminate\Support\Facades\Config;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\PaginatedDataCollection;

final readonly class AttributeRepository
{
    /**
     * @param AttributeIndexDto $dto
     *
     * @return PaginatedDataCollection<int, AttributeDto>
     * @throws ServerException
     */
    public function search(AttributeIndexDto $dto): PaginatedDataCollection
    {
        $per_page = Config::get('pagination.per_page');

        if (!is_int($per_page)) {
            throw new ServerException(Exceptions::SERVER_BAD_CONFIG_TYPE);
        }

        $attributes = Attribute::searchByCriteria($dto->toArray())
            ->orderBy('order')
            ->paginate($per_page);

        return AttributeDto::paginatedCollection($attributes);
    }

    /**
     * @param string[] $sets
     *
     * @return DataCollection<int, AttributeDto>
     */
    public function getAllGlobal(array $sets = []): DataCollection
    {
        return AttributeDto::staticCollection(Attribute::query()
            ->whereHas(
                'productSets',
                fn ($query) => $query->whereIn('product_set_id', $sets),
            )
            ->orWhere('global', '=', true)
            ->get());
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
