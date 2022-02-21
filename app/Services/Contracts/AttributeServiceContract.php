<?php

namespace App\Services\Contracts;

use App\Dtos\AttributeDto;
use App\Models\Attribute;

interface AttributeServiceContract
{
    public function create(AttributeDto $dto): Attribute;

    public function update(Attribute $attribute, AttributeDto $dto): Attribute;

    public function delete(Attribute $attribute): void;
}
