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
