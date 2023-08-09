<?php

declare(strict_types=1);

namespace Domain\Metadata;

use Domain\Metadata\Dtos\MetadataDto;
use Domain\Metadata\Models\Metadata;
use Illuminate\Support\Str;
use Spatie\LaravelData\DataCollection;

final readonly class MetadataRepository
{
    /**
     * @return DataCollection<int, MetadataDto>
     */
    public function getAll(string $id, bool $with_private): DataCollection
    {
        $query = Metadata::query()
            ->where('model_id', '=', $id);

        if (!$with_private) {
            $query->where('public', '=', true);
        }

        return MetadataDto::staticCollection($query->get());
    }

    /**
     * @param MetadataDto[] $dtos
     */
    public function upsert(array $dtos): void
    {
        Metadata::query()->upsert(
            array_map(fn ($dto) => [...$dto->toArray(), 'id' => Str::uuid()], $dtos),
            ['name', 'model_id', 'model_type'],
        );
    }

    /**
     * @param string[] $names
     */
    public function deleteBatch(string $model_id, array $names): void
    {
        Metadata::query()
            ->where('model_id', '=', $model_id)
            ->whereIn('name', $names)
            ->delete();
    }
}
