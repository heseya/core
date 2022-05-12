<?php

namespace App\Services;

use App\Dtos\StatusDto;
use App\Models\Status;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\StatusServiceContract;
use Heseya\Dto\Missing;

class StatusService implements StatusServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService
    ) {
    }

    public function store(StatusDto $dto): Status
    {
        $status = Status::create($dto->toArray());

        if (!($dto->getMetadata() instanceof Missing)) {
            $this->metadataService->sync($status, $dto->getMetadata());
        }

        return $status;
    }

    public function update(Status $status, StatusDto $dto): Status
    {
        $status->update($dto->toArray());

        return $status;
    }

    public function destroy(Status $status): void
    {
        $status->delete();
    }
}
