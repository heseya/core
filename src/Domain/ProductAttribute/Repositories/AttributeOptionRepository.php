<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Repositories;

use Domain\ProductAttribute\Dtos\AttributeOptionDto;
use Domain\ProductAttribute\Models\AttributeOption;

final readonly class AttributeOptionRepository
{
    public function create(AttributeOptionDto $dto): AttributeOption
    {
        /** @var AttributeOption $attributeOption */
        $attributeOption = AttributeOption::query()->make(
            array_merge(
                [
                    'index' => AttributeOption::withTrashed()->where('attribute_id', '=', $dto->attribute_id)->count() + 1,
                ],
                $dto->toArray(),
            )
        );

        if ($dto->translations) {
            foreach ($dto->translations as $lang => $translation) {
                $attributeOption->setLocale($lang)->fill($translation);
            }
        }

        $attributeOption->save();

        return $attributeOption;
    }

    public function update(string $id, AttributeOptionDto $dto): AttributeOption
    {
        /** @var AttributeOption $attributeOption */
        $attributeOption = AttributeOption::query()->findOrFail($id);

        if ($dto->translations) {
            foreach ($dto->translations as $lang => $translation) {
                $attributeOption->setLocale($lang)->fill($translation);
            }
        }

        $attributeOption->fill($dto->toArray());
        $attributeOption->save();

        return $attributeOption;
    }
}
