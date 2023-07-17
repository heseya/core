<?php

namespace App\Services;

use App\DTO\OrderStatus\OrderStatusCreateDto;
use App\DTO\OrderStatus\OrderStatusUpdateDto;
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
    public function store(OrderStatusCreateDto $dto): Status
    {
        $status = new Status($dto->toArray());

        foreach ($dto->translations as $lang => $translations) {
            $status->setLocale($lang)->fill($translations);
        }

        $this->translationService->checkPublished($status, ['name']);

        $status->save();

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync($status, $dto->metadata);
        }

        return $status;
    }

    /**
     * @throws ClientException
     * @throws PublishingException
     */
    public function update(Status $status, OrderStatusUpdateDto $dto): Status
    {
        if (!($dto->cancel instanceof Optional) && $dto->cancel !== $status->cancel && $status->orders()->count() > 0) {
            // cannot unset cancel when any order with this status exists
            throw new ClientException(Exceptions::CLIENT_STATUS_USED);
        }

        $status->fill($dto->toArray());

        if (!($dto->translations instanceof Optional)) {
            foreach ($dto->translations as $lang => $translations) {
                $status->setLocale($lang)->fill($translations);
            }
            $this->translationService->checkPublished($status, ['name']);
        }

        $status->save();

        if (!($dto->metadata instanceof Optional)) {
            $this->metadataService->sync($status, $dto->metadata);
        }

        return $status;
    }

    public function destroy(Status $status): void
    {
        $status->delete();
    }
}
