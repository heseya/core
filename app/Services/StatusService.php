<?php

namespace App\Services;

use App\DTO\OrderStatus\OrderStatusDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Exceptions\PublishingException;
use App\Models\Status;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\StatusServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Spatie\LaravelData\Optional;

readonly class StatusService implements StatusServiceContract
{
    public function __construct(
        private MetadataServiceContract $metadataService,
        private TranslationServiceContract $translationService,
    ) {}

    /**
     * @throws PublishingException
     */
    public function store(OrderStatusDto $dto): Status
    {
        $status = new Status($dto->toArray());

        foreach ($dto->translations as $lang => $translations) {
            $status->setLocale($lang)->fill($translations);
        }

        $this->translationService->checkPublished($status, ['name']);

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync($status, $dto->metadata->toArray());
        }

        $status->save();

        return $status;
    }

    /**
     * @throws ClientException
     */
    public function update(Status $status, OrderStatusDto $dto): Status
    {
        if ($status->orders()->count() > 0 && $dto->cancel !== $status->cancel) {
            // cannot unset cancel when any order with this status exists
            throw new ClientException(Exceptions::CLIENT_STATUS_USED);
        }

        $status->update($dto->toArray());

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync($status, $dto->metadata->toArray());
        }

        return $status;
    }

    public function destroy(Status $status): void
    {
        $status->delete();
    }
}
