<?php

namespace App\Services\Contracts;

use App\Dtos\AttributeOptionDto;
use App\Models\AttributeOption;

interface AttributeOptionServiceContract
{
    public function create(string $attributeId, AttributeOptionDto $dto): AttributeOption;

    public function updateOrCreate(string $attributeId, AttributeOptionDto $dto): AttributeOption;

    public function delete(AttributeOption $attributeOption): void;

    public function deleteAttributeOptions(string $attributeId): void;
}
