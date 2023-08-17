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
        /** @var Attribute $attribute */
        $attribute = Attribute::query()->make($dto->toArray());

        foreach ($dto->translations as $lang => $translation) {
            $attribute->setLocale($lang)->fill($translation);
        }
        $attribute->save();

        return $attribute;
    }

    /**
     * Update given model, returns number of rows affected.
     */
    public function update(string $id, AttributeUpdateDto $dto): bool
    {
        /** @var Attribute $attribute */
        $attribute = Attribute::query()->where('id', '=', $id)->first();

        foreach ($dto->translations as $lang => $translation) {
            $attribute->setLocale($lang)->fill($translation);
        }
        $attribute->fill($dto->toArray());

        return $attribute->save();
    }

    public function delete(string $id): void
    {
        Attribute::query()->where('id', '=', $id)->delete();
    }
}