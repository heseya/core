<?php

declare(strict_types=1);

namespace Domain\Metadata;

use App\Models\Metadata;
use Domain\Metadata\Dtos\MetadataDto;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Spatie\LaravelData\DataCollection;

final readonly class MetadataRepository
{
    /**
     * @param class-string $class
     * @param string[] $ids
     * @param bool $with_private
     *
     * @return DataCollection<int, MetadataDto>
     */
    public function getAll(string $class, array $ids, bool $with_private): DataCollection
    {
        $query = Metadata::query()
            ->where('model_type', '=', $class)
            ->whereIn('model_id', $ids);

        if (!$with_private) {
            $query->where('public', '=', true);
        }

        return MetadataDto::staticCollection($query->get());
    }

    /**
     * @param class-string $class
     */
    public function updateOrCreate(string $class, string $id, MetadataUpdateDto $dto): void
    {
        Metadata::query()->updateOrCreate([
            'name' => $dto->name,
            'model_type' => $class,
            'model_id' => $id,
        ], $dto->toArray());
    }

    /**
     * @param class-string $class
     */
    public function delete(string $class, string $id, string $name): void
    {
        Metadata::query()
            ->where('model_type', '=', $class)
            ->where('model_id', '=', $id)
            ->where('name', '=', $name)
            ->delete();
    }
}
