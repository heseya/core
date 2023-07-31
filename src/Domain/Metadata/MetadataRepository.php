<?php

declare(strict_types=1);

namespace Domain\Metadata;

use App\DTO\Metadata\MetadataDto;
use App\Models\Metadata;

final readonly class MetadataRepository
{
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
