<?php

declare(strict_types=1);

namespace Domain\Metadata;

use Domain\Metadata\Dtos\MetadataUpdateDto;

final readonly class MetadataService
{
    public function __construct(
        private MetadataRepository $repository,
    ) {}

    /**
     * Get all metadata for given ids.
     * This function returns a 2 element array where first element is array af public metadata
     * and the second one is array of private.
     *
     * @return array<string, bool|float|int|string|null>[]
     */
    public function getAll(string $model_id, bool $with_private): array
    {
        $public = [];
        $private = [];

        foreach ($this->repository->getAll($model_id, $with_private) as $metadata) {
            if ($metadata->public) {
                $public[$metadata->name] = $metadata->value;
            } else {
                $private[$metadata->name] = $metadata->value;
            }
        }

        return [$public, $private];
    }

    /**
     * @param class-string $class
     * @param MetadataUpdateDto[] $metadata
     */
    public function sync(string $class, string $model_id, array $metadata): void
    {
        $deleted = [];

        foreach ($metadata as $dto) {
            if ($dto->value === null) {
                $deleted[] = $dto->name;
                continue;
            }

            $this->repository->updateOrCreate($class, $model_id, $dto);
        }

        if (count($deleted) > 0) {
            $this->repository->deleteBatch($model_id, $deleted);
        }
    }
}
