<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Repositories;

use Domain\Language\LanguageService;
use Domain\ProductAttribute\Dtos\AttributeOptionDto;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Spatie\LaravelData\Optional;

final readonly class AttributeOptionRepository
{
    public function firstOrCreate(AttributeOptionDto $dto, bool $existingId = false): AttributeOption
    {
        /** @var Attribute $attribute */
        $attribute = Attribute::query()->where('id', '=', $dto->attribute_id)->firstOrFail();
        $query = AttributeOption::query()
            ->where('attribute_id', '=', $dto->attribute_id);

        if ($attribute->type === AttributeType::NUMBER && !$dto->value_number instanceof Optional) {
            $query->where('value_number', '=', $dto->value_number);
        }

        if ($attribute->type === AttributeType::DATE && !$dto->value_date instanceof Optional) {
            $query->where('value_date', '=', $dto->value_date);
        }

        if (in_array($attribute->type, [AttributeType::SINGLE_OPTION, AttributeType::MULTI_CHOICE_OPTION], true) && $dto->translations) {
            /** @var string $defaultLanguage */
            $defaultLanguage = App::make(LanguageService::class)->defaultLanguage()->getKey();

            if (array_key_exists($defaultLanguage, $dto->translations) && isset($dto->translations[$defaultLanguage]['name'])) {
                $value = '%"' . $defaultLanguage . '":"' . preg_replace('/\s+/', ' ', $dto->translations[$defaultLanguage]['name']) . '"%';
                $query->where('name', 'like', $value);
            }
        }

        /** @var AttributeOption|null $attributeOption */
        $attributeOption = $query->first();

        if ($attributeOption) {
            return $attributeOption;
        }

        /** @var AttributeOption $attributeOption */
        $attributeOption = AttributeOption::query()->make(
            array_merge(
                [
                    'index' => AttributeOption::withTrashed()->where('attribute_id', '=', $dto->attribute_id)->count() + 1,
                    'order' => AttributeOption::query()->where('attribute_id', '=', $dto->attribute_id)->count(),
                ],
                $existingId ? Arr::except($dto->toArray(), ['id']) : $dto->toArray(),
            ),
        );

        if ($dto->translations) {
            foreach ($dto->translations as $lang => $translation) {
                $attributeOption->setLocale($lang)->fill($this->trimSpaces($translation));
            }
        }

        $attributeOption->save();

        return $attributeOption;
    }

    public function update(AttributeOption $attributeOption, AttributeOptionDto $dto): AttributeOption
    {
        if ($dto->translations) {
            foreach ($dto->translations as $lang => $translation) {
                $attributeOption->setLocale($lang)->fill($this->trimSpaces($translation));
            }
        }

        $attributeOption->fill($dto->toArray());
        $attributeOption->save();

        return $attributeOption;
    }

    /**
     * @param array<string, string> $translation
     *
     * @return array<string, string>
     */
    private function trimSpaces(array $translation): array
    {
        foreach ($translation as $key => $value) {
            $translation[$key] = preg_replace('/\s+/', ' ', $value) ?? '';
        }

        return $translation;
    }
}
