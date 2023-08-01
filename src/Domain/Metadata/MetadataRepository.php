<?php

declare(strict_types=1);

namespace Domain\Metadata;

use App\DTO\Metadata\MetadataDto;
use App\Models\Metadata;
use Spatie\LaravelData\DataCollection;

final readonly class MetadataRepository
{
    /**
     * @param string $class
     * @param string $id
     * @param bool $with_private
     *
     * @return DataCollection<int, MetadataDto>
     */
    public function getAll(string $class, string $id, bool $with_private): DataCollection
    {
        $query = Metadata::query()
            ->where('model_type', '=', $class)
            ->where('model_id', '=', $id);

        if (!$with_private) {
            $query->where('public', '=', true);
        }

        return MetadataDto::staticCollection($query->get());
    }

    public function updateOrCreate(string $class, string $id, MetadataDto $dto): void
    {
        Metadata::query()->updateOrCreate([
            'name' => $dto->name,
            'model_type' => $class,
            'model_id' => $id,
        ], $dto->toArray());
    }

    public function delete(string $class, string $id, string $name): void
    {
        Metadata::query()
            ->where('model_type', '=', $class)
            ->where('model_id', '=', $id)
            ->where('name', '=', $name)
            ->delete();
    }
}
