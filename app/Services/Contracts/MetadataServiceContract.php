<?php

namespace App\Services\Contracts;

use App\Dtos\MetadataDto;
use App\Models\Model;
use App\Models\Role;

interface MetadataServiceContract
{
    public function updateOrCreate(Model|Role $model, MetadataDto $dto);

    public function sync(Model|Role $model, array $metadata): void;

    public function returnModel(array $routeSegments): Model|Role|null;
}
