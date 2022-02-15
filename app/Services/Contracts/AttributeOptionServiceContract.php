<?php

namespace App\Services\Contracts;

use App\Dtos\AttributeOptionDto;
use App\Models\AttributeOption;

interface AttributeOptionServiceContract
{
    public function updateOrCreate(string $attributeId, AttributeOptionDto $dto): AttributeOption;

    public function deleteAttributeOptions(string $attributeId): void;
}
