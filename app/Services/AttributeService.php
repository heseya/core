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
        $this->attributeOptionService->deleteAll($attribute->getKey());

        $attribute->delete();
    }

    public function sync(Product $product, array $data): void
    {
        $attributes = collect($data)->map(
            fn ($option, $attribute) => [
                'attribute_id' => $attribute,
                'option_id' => $option,
            ]
        );

        $product->attributes()->sync($attributes->values()->toArray());
    }

    public function updateMinMax(Attribute $attribute): void
    {
        $attribute->refresh();

        if ($attribute->type->value === AttributeType::NUMBER) {
            $attribute->min_number = $attribute->options->min('value_number');
            $attribute->max_number = $attribute->options->max('value_number');
        }

        if ($attribute->type->value === AttributeType::DATE) {
            $attribute->min_date = $attribute->options->min('value_date');
            $attribute->max_date = $attribute->options->max('value_date');
        }

        $attribute->update();
    }

    protected function processAttributeOptions(Attribute &$attribute, AttributeDto $dto): Attribute
    {
        $attribute->options
            ->whereNotIn('id', array_map(fn ($option) => $option->getId(), $dto->getOptions()))
            ->each(
                fn ($missingOption) => $this->attributeOptionService->delete($missingOption)
            );

        foreach ($dto->getOptions() as $option) {
            $this->attributeOptionService->updateOrCreate($attribute->getKey(), $option);
        }

        return $attribute->refresh();
    }
}
