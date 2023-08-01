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
     * Get all metadata for given model.
     *
     * @param class-string $class
     * @param string[] $ids
     *
     * @return array<string, array<string, bool|float|int|string|null>>[]
     */
    public function getAll(string $class, array $ids, bool $with_private): array
    {
        $return = [];

        foreach ($this->repository->getAll($class, $ids, $with_private) as $metadata) {
            if ($metadata->public) {
                $return[$metadata->model_id]['public'][$metadata->name] = $metadata->value;
            } else {
                $return[$metadata->model_id]['private'][$metadata->name] = $metadata->value;
            }
        }

        return $return;
    }

    /**
     * @param class-string $class
     * @param MetadataUpdateDto[] $metadata
     */
    public function sync(string $class, string $id, array $metadata): void
    {
        foreach ($metadata as $dto) {
            $this->processMetadata($class, $id, $dto);
        }
    }

    /**
     * @param class-string $class
     */
    private function processMetadata(string $class, string $id, MetadataUpdateDto $dto): void
    {
        if ($dto->value === null) {
            $this->repository->delete($class, $id, $dto->name);

            return;
        }

        $this->repository->updateOrCreate($class, $id, $dto);
    }
}
