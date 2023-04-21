<?php

namespace App\Services;

use App\Dtos\MediaAttachmentDto;
use App\Dtos\MediaAttachmentUpdateDto;
use App\Models\Media;
use App\Models\Model;
use App\Models\MediaAttachment;
use App\Services\Contracts\MediaAttachmentServiceContract;
use App\Services\Contracts\MediaServiceContract;

readonly class MediaAttachmentService implements MediaAttachmentServiceContract
{
    public function __construct(
        private MediaServiceContract $mediaService,
    ) {
    }
    public function addAttachment(Model $model, MediaAttachmentDto $dto, ?string $label = null): MediaAttachment
    {
        Media::query()->findOrFail($dto->media_id);

        return MediaAttachment::query()->create([
            'model_id' => $model->getKey(),
            'model_type' => $model::class,
            'label' => $label,
        ] + $dto->toArray());
    }

    public function editAttachment(MediaAttachment $attachment, MediaAttachmentUpdateDto $dto): MediaAttachment
    {
        $attachment->update($dto->toArray());

        return $attachment;
    }

    public function removeAttachment(MediaAttachment $attachment): MediaAttachment
    {
        $this->mediaService->destroy($attachment->media);
        $attachment->delete();

        return $attachment;
    }
}
