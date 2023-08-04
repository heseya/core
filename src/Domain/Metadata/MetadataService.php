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
     *
     * @return array<string, bool|float|int|string|null>[]
     */
    public function getAll(string $id, bool $with_private): array
    {
        $public = [];
        $private = [];

        foreach ($this->repository->getAll($id, $with_private) as $metadata) {
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
            $this->repository->delete($id, $dto->name);

            return;
        }

        $this->repository->updateOrCreate($class, $id, $dto);
    }
}
