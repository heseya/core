<?php

namespace App\Services\Contracts;

use App\Dtos\MetadataPersonalListDto;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface MetadataServiceContract
{
    public function updateOrCreate(Model $model, MetadataUpdateDto $dto): void;

    public function sync(Model $model, array $metadata): void;

    public function returnModel(array $routeSegments): Model|null;

    public function updateOrCreateMyPersonal(MetadataPersonalListDto $dto): Collection;

    public function updateOrCreateUserPersonal(MetadataPersonalListDto $dto, string $userId): Collection;
}
