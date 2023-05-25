<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Enums\MediaAttachmentType;
use App\Enums\VisibilityType;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class MediaAttachmentUpdateDto extends Dto implements InstantiateFromRequest
{
    public function __construct(
        public readonly string|Missing $name = new Missing(),
        public readonly MediaAttachmentType|Missing $type = new Missing(),
        public readonly string|null|Missing $description = new Missing(),
        public readonly VisibilityType|Missing $visibility = new Missing(),
    ) {
    }

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            type: $request->enum('type', MediaAttachmentType::class) ?? new Missing(),
            description: $request->has('description') ? $request->input('description') : new Missing(),
            visibility: $request->enum('visibility', VisibilityType::class) ?? new Missing(),
        );
    }
}
