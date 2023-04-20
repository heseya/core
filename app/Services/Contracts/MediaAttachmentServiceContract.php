<?php

namespace App\Services\Contracts;

use App\Dtos\MediaAttachmentDto;
use App\Dtos\MediaAttachmentUpdateDto;
use App\Models\Model;
use App\Models\MediaAttachment;

interface MediaAttachmentServiceContract
{
    public function addAttachment(Model $model, MediaAttachmentDto $dto): MediaAttachment;
    public function editAttachment(MediaAttachment $attachment, MediaAttachmentUpdateDto $dto): MediaAttachment;
    public function removeAttachment(MediaAttachment $attachment): MediaAttachment;
}
