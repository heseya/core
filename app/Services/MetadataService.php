<?php

namespace App\Services;

use App\Dtos\MetadataPersonalListDto;
use App\Models\Discount;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\MetadataServiceContract;
use Domain\Banner\Models\Banner;
use Domain\Metadata\Dtos\MetadataPersonalDto;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\Page\Page;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MetadataService implements MetadataServiceContract
{
    public function sync(Model $model, array $metadata): void
    {
        foreach ($metadata as $meta) {
            if ($meta instanceof MetadataPersonalDto) {
                $this->processMetadata($model, $meta, 'metadataPersonal');
            } else {
                $this->updateOrCreate($model, $meta);
            }
        }
    }

    public function updateOrCreate(Model $model, MetadataUpdateDto $dto): void
    {
        $this->processMetadata($model, $dto, $dto->public ? 'metadata' : 'metadataPrivate');
    }

    /**
     * @param string[] $routeSegments
     */
    public function returnModel(array $routeSegments): Model|null
    {
        $className = match ($routeSegments[0]) {
            'pages' => Page::class,
            'product-sets' => ProductSet::class,
            'sales', 'coupons' => Discount::class,
            'attributes' => $routeSegments[2] === 'options' ? AttributeOption::class : Attribute::class,
            'banners' => Banner::class,
            default => '',
        };

        if (class_exists($className)) {
            return new $className();
        }

        $className = 'App\\Models\\' . Str::studly(Str::singular($routeSegments[0]));

        if (class_exists($className)) {
            // @phpstan-ignore-next-line
            return new $className();
        }

        $className = 'App\\Models\\' . Str::studly($routeSegments[0]);

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

    private function processMetadata(
        Model|Role $model,
        MetadataPersonalDto|MetadataUpdateDto $dto,
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
