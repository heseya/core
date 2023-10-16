<?php

namespace App\Services;

use App\Dtos\MetadataDto;
use App\Dtos\MetadataPersonalDto;
use App\Dtos\MetadataPersonalListDto;
use App\Models\Model;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\MetadataServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MetadataService implements MetadataServiceContract
{
    public function sync(Model|Role $model, array $metadata): void
    {
        foreach ($metadata as $meta) {
            if ($meta instanceof MetadataPersonalDto) {
                $this->processMetadata($model, $meta, 'metadataPersonal');
            } else {
                $this->updateOrCreate($model, $meta);
            }
        }
    }

    public function updateOrCreate(Model|Role $model, MetadataDto $dto): void
    {
        $this->processMetadata($model, $dto, $dto->isPublic() ? 'metadata' : 'metadataPrivate');
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
            // @phpstan-ignore-next-line
            return new $className();
        }

        $className = 'App\\Models\\' . Str::studly($class);

        if (class_exists($className)) {
            // @phpstan-ignore-next-line
            return new $className();
        }

        return null;
    }

    public function updateOrCreateMyPersonal(MetadataPersonalListDto $dto): Collection
    {
        $user = Auth::user();
        if ($user !== null) {
            foreach ($dto->getMetadata() as $metadata) {
                $this->processMetadata($user, $metadata, 'metadataPersonal');
            }

            return $user->metadataPersonal;
        }

        return Collection::make();
    }

    public function updateOrCreateUserPersonal(MetadataPersonalListDto $dto, string $userId): Collection
    {
        $user = User::findOrFail($userId);
        foreach ($dto->getMetadata() as $metadata) {
            $this->processMetadata($user, $metadata, 'metadataPersonal');
        }

        return $user->metadataPersonal;
    }

    private function isAttributeOption(array $segments): bool
    {
        return $segments[2] === 'options';
    }

    private function processMetadata(Model|Role $model, MetadataDto|MetadataPersonalDto $dto, string $relation): void
    {
        $query = $model->{$relation}();

        if ($dto->getValue() === null) {
            $query->where('name', $dto->getName())->delete();

            return;
        }

        $query->updateOrCreate(
            ['name' => $dto->getName()],
            $dto->toArray(),
        );
    }
}
