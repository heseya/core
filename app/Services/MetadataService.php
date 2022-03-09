<?php

namespace App\Services;

use App\Dtos\MetadataDto;
use App\Models\Model;
use App\Models\Role;
use App\Services\Contracts\MetadataServiceContract;
use Illuminate\Support\Str;

class MetadataService implements MetadataServiceContract
{
    public function updateOrCreate(Model|Role $model, MetadataDto $dto)
    {
        $model = $dto->isPublic() ? $model->metadata() : $model->metadataPrivate();

        if ($dto->getValue() === null) {
            $model->where('name', $dto->getName())
                ->delete();
        } else {
            $model->updateOrCreate(
                [
                    'name' => $dto->getName(),
                ],
                $dto->toArray()
            );
        }
    }

    public function returnModel(array $routeSegments): Model|Role|null
    {
        $segment = collect($routeSegments)->first();
        $className = 'App\\Models\\' . Str::studly(Str::singular($segment));

        if (class_exists($className)) {
            return new $className();
        }

        $className = 'App\\Models\\' . Str::studly($segment);

        if (class_exists($className)) {
            return new $className();
        }

        return null;
    }
}
