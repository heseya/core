<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Repositories;

use Domain\ProductAttribute\Dtos\AttributeCreateDto;
use Domain\ProductAttribute\Dtos\AttributeUpdateDto;
use Domain\ProductAttribute\Models\Attribute;
use Spatie\LaravelData\Optional;

final readonly class AttributeRepository
{
    public function getOne(string $search): Attribute
    {
        return Attribute::query()
            ->where('id', $search)
            ->orWhere('slug', $search)
            ->firstOrFail();
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
        $attribute = Attribute::query()->where('id', '=', $id)->firstOrFail();

        if (!($dto->translations instanceof Optional)) {
            foreach ($dto->translations as $lang => $translation) {
                $attribute->setLocale($lang)->fill($translation);
            }
        }
        $attribute->fill($dto->toArray());

        return $attribute->save();
    }

    public function delete(string $id): void
    {
        Attribute::query()->where('id', '=', $id)->delete();
    }
}
