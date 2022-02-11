<?php

namespace App\Services;

use App\Dtos\AttributeDto;
use App\Models\Attribute;
use App\Services\Contracts\AttributeServiceContract;

class AttributeService implements AttributeServiceContract
{
    public function create(AttributeDto $dto): Attribute
    {
        return Attribute::create($dto->toArray());
    }

    public function update(Attribute $attribute, AttributeDto $dto): Attribute
    {
        $attribute->update($dto->toArray());

        return $attribute;
    }

    public function delete(Attribute $attribute): void
    {
        $attribute->delete();
    }
}
