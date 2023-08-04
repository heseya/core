<?php

namespace App\Services\Contracts;

use App\Dtos\MetadataPersonalListDto;
use App\Models\Model;
use App\Models\Role;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Illuminate\Support\Collection;

interface MetadataServiceContract
{
    public function updateOrCreate(Model|Role $model, MetadataUpdateDto $dto): void;

    public function sync(Model|Role $model, array $metadata): void;

    public function returnModel(array $routeSegments): Model|Role|null;

    public function updateOrCreateMyPersonal(MetadataPersonalListDto $dto): Collection;

    public function updateOrCreateUserPersonal(MetadataPersonalListDto $dto, string $userId): Collection;
}
