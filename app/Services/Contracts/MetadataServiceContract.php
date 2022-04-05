<?php

namespace App\Services\Contracts;

use App\Dtos\MetadataDto;
use App\Models\Model;
use App\Models\Role;

interface MetadataServiceContract
{
    public function updateOrCreate(Model|Role $model, MetadataDto $dto);
}
