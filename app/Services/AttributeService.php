<?php

namespace App\Services;

use App\Dtos\AttributeDto;
use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\Product;
use App\Services\Contracts\AttributeOptionServiceContract;
use App\Services\Contracts\AttributeServiceContract;

class AttributeService implements AttributeServiceContract
{
    public function __construct(private AttributeOptionServiceContract $attributeOptionService)
    {
    }

    public function create(AttributeDto $dto): Attribute
    {
        $attribute = Attribute::create($dto->toArray());

        $this->processAttributeOptions($attribute, $dto);

        return $attribute;
    }

    public function update(Attribute $attribute, AttributeDto $dto): Attribute
    {
        $attribute->update($dto->toArray());

        $this->processAttributeOptions($attribute, $dto);

        return $attribute;
    }

    public function delete(Attribute $attribute): void
    {
        $this->attributeOptionService->deleteAttributeOptions($attribute->getKey());

        $attribute->delete();
    }

    public function sync(Product $product, array $data): void
    {
        $attributes = array_map(function ($row) {
            $explode = explode(',', $row);

            return [
                'attribute_id' => $explode[0],
                'option_id' => $explode[1],
            ];
        }, $data);

        $product->attributes()->sync($attributes);
    }

    public function updateMinMax(Attribute $attribute, ?float $number, ?string $date): void
    {
        if ($attribute->type->value === AttributeType::NUMBER) {
            if ($number < $attribute->min_number || $attribute->min_number === null) {
                $attribute->min_number = $number;
            }

            if ($number > $attribute->max_number || $attribute->max_number === null) {
                $attribute->max_number = $number;
            }
        }

        if ($attribute->type->value === AttributeType::DATE) {
            if ($date < $attribute->min_date || $attribute->min_date === null) {
                $attribute->min_date = $date;
            }

            if ($date > $attribute->max_date || $attribute->max_date === null) {
                $attribute->max_date = $date;
            }
        }

        $attribute->update();
    }

    protected function processAttributeOptions(Attribute &$attribute, AttributeDto $dto): Attribute
    {
        foreach ($dto->getOptions() as $option) {
            $this->attributeOptionService->updateOrCreate($attribute->getKey(), $option);
        }

        return $attribute;
    }
}
