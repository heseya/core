<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Enums\MediaAttachmentType;
use App\Enums\VisibilityType;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class MediaAttachmentDto extends Dto implements InstantiateFromRequest
{
    public function __construct(
        public readonly string $media_id,
        public readonly string $name,
        public readonly MediaAttachmentType $type,
        public readonly VisibilityType $visibility,
        public readonly string|Missing $label = new Missing(),
    ) {
    }

    /**
     * @throws DtoException
     */
    public static function instantiateFromRequest(FormRequest $request): self
    {
        $type = $request->enum('type', MediaAttachmentType::class);
        $visibility = $request->enum('visibility', VisibilityType::class);

        if ($type === null || $visibility === null) {
            throw new DtoException('Invalid type or visibility');
        }

        return new self(
            media_id: $request->input('media_id'),
            name: $request->input('name'),
            type: $type,
            visibility: $visibility,
            label: $request->input('label') ?? new Missing()
        );
    }
}
