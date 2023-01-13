<?php

namespace App\Services;

use App\Dtos\StatusDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
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
        if (
            $status->orders()->count() > 0
            && !$dto->getCancel() instanceof Missing
            && $dto->getCancel() !== $status->cancel
        ) {
            throw new ClientException(Exceptions::CLIENT_STATUS_USED);
        }

        $status->update($dto->toArray());

        return $status;
    }

    public function destroy(Status $status): void
    {
        $status->delete();
    }
}
