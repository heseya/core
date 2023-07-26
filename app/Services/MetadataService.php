<?php

namespace App\Services;

use App\DTO\Metadata\MetadataDto;
use App\DTO\Metadata\MetadataPersonalDto;
use App\Dtos\MetadataPersonalListDto;
use App\Models\Model;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\MetadataServiceContract;
use Illuminate\Database\Eloquent\Builder;
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
        $this->processMetadata($model, $dto, $dto->public ? 'metadata' : 'metadataPrivate');
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

        if ($user instanceof User) {
            foreach ($dto->metadata as $metadata) {
                $this->processMetadata($user, $metadata, 'metadataPersonal');
            }

            return $user->metadataPersonal;
        }

        return Collection::make();
    }

    public function updateOrCreateUserPersonal(MetadataPersonalListDto $dto, string $userId): Collection
    {
        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        foreach ($dto->metadata as $metadata) {
            $this->processMetadata($user, $metadata, 'metadataPersonal');
        }

        return $user->metadataPersonal;
    }

    private function isAttributeOption(array $segments): bool
    {
        return $segments[2] === 'options';
    }

    private function processMetadata(
        Model|Role $model,
        MetadataDto|MetadataPersonalDto $dto,
        string $relation,
    ): void {
        /** @var Builder $query */
        $query = $model->{$relation}();

        if ($dto->value === null) {
            $query->where('name', $dto->name)->delete();

            return;
        }

        $query->updateOrCreate(
            ['name' => $dto->name],
            [
                'value' => $dto->value,
                'value_type' => $dto->value_type,
                'public' => $dto->public ?? true,
            ],
        );
    }
}
