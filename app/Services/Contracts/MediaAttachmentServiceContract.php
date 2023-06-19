<?php

namespace App\Services\Contracts;

use App\Dtos\MediaAttachmentDto;
use App\Dtos\MediaAttachmentUpdateDto;
use App\Models\MediaAttachment;
use App\Models\Model;

interface MediaAttachmentServiceContract
{
    public function addAttachment(Model $model, MediaAttachmentDto $dto, ?string $label = null): MediaAttachment;

    public function editAttachment(MediaAttachment $attachment, MediaAttachmentUpdateDto $dto): MediaAttachment;

    public function removeAttachment(MediaAttachment $attachment): MediaAttachment;
}
