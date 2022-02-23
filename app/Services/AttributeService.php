<?php

namespace App\Services;

use App\Dtos\AttributeDto;
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

    protected function processAttributeOptions(Attribute &$attribute, AttributeDto $dto): Attribute
    {
        foreach ($dto->getOptions() as $option) {
            $this->attributeOptionService->updateOrCreate($attribute->getKey(), $option);
        }

        return $attribute;
    }
}
