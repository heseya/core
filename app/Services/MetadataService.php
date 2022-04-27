<?php

namespace App\Services;

use App\Dtos\MetadataDto;
use App\Models\Model;
use App\Models\Role;
use App\Services\Contracts\MetadataServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MetadataService implements MetadataServiceContract
{
    public function sync(Model|Role $model, array $metadata): void
    {
        foreach ($metadata as $meta) {
            $this->updateOrCreate($model, $meta);
        }
    }

    public function updateOrCreate(Model|Role $model, MetadataDto $dto): void
    {
        $query = $dto->isPublic() ? $model->metadata() : $model->metadataPrivate();

        if ($dto->getValue() === null) {
            $query->where('name', $dto->getName())->delete();

            return;
        }

        $query->updateOrCreate(
            ['name' => $dto->getName()],
            $dto->toArray(),
        );
    }

    public function returnModel(array $routeSegments): Model|Role|null
    {
        $segments = Collection::make($routeSegments);

        $segment = $segments->first();

        $class = match ($segment) {
            'sales', 'coupons' => 'discounts',
            'attributes' => $this->isAttributeOption($segments->toArray()) ? 'attribute_options' : 'attributes',
            default => $segment,
        };
        $className = 'App\\Models\\' . Str::studly(Str::singular($class));

        if (class_exists($className)) {
            return new $className();
        }

        $className = 'App\\Models\\' . Str::studly($class);

        if (class_exists($className)) {
            return new $className();
        }

        return null;
    }

    private function isAttributeOption(array $segments): bool
    {
        return $segments[2] === 'options';
    }
}
