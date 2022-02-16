<?php

namespace App\Services;

use App\Dtos\AttributeDto;
use App\Http\Resources\AttributeOptionResource;
use App\Models\Attribute;
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

    protected function processAttributeOptions(Attribute &$attribute, AttributeDto $dto): Attribute
    {
        foreach ($dto->getOptions() as $option) {
            $this->attributeOptionService->updateOrCreate($attribute->getKey(), $option);
        }

        return $attribute;
    }
}
