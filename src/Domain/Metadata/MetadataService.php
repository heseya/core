<?php

declare(strict_types=1);

namespace Domain\Metadata;

use App\DTO\Metadata\MetadataDto;

final readonly class MetadataService
{
    public function __construct(
        private MetadataRepository $repository,
    ) {}

    /**
     * Get all metadata for given model.
     *
     * @return array<string, bool|float|int|string|null>[]
     */
    public function getAll(string $class, string $id, bool $with_private): array
    {
        $public = [];
        $private = [];

        foreach ($this->repository->getAll($class, $id, $with_private) as $metadata) {
            if ($metadata->public) {
                $public[$metadata->name] = $metadata->value;
            } else {
                $private[$metadata->name] = $metadata->value;
            }
        }

        return [$public, $private];
    }

    /**
     * @param MetadataDto[] $metadata
     */
    public function sync(string $class, string $id, array $metadata): void
    {
        foreach ($metadata as $dto) {
            $this->processMetadata($class, $id, $dto);
        }
    }

    private function processMetadata(string $class, string $id, MetadataDto $dto): void
    {
        if ($dto->value === null) {
            $this->repository->delete($class, $id, $dto->name);

            return;
        }

        $this->repository->updateOrCreate($class, $id, $dto);
    }
}
