<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Http\Requests\MediaStoreRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class MediaDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public function __construct(
        public readonly UploadedFile|Missing|null $file,
        public readonly string|Missing $id = new Missing(),
        public readonly string|Missing $url = new Missing(),
        public readonly MediaType|Missing $type = new Missing(),
        public readonly string|null|Missing $alt = new Missing(),
        public readonly string|null|Missing $slug = new Missing(),
        public readonly MediaSource $source = MediaSource::SILVERBOX,
        public readonly array|Missing $metadata = new Missing(),
    ) {
    }

    /**
     * @throws DtoException
     */
    public static function instantiateFromRequest(FormRequest|MediaStoreRequest $request): self
    {
        $file = $request->file('file', new Missing());
        if (is_array($file)) {
            $file = $file[0];
        }

        return new self(
            file: $file,
            id: $request->input('id') ?? new Missing(),
            url: $request->input('url', new Missing()),
            type: $request->enum('type', MediaType::class) ?? new Missing(),
            alt: $request->input('alt', new Missing()),
            slug: $request->input('slug', new Missing()),
            source: $request->enum('source', MediaSource::class) ?? MediaSource::SILVERBOX,
            metadata: self::mapMetadata($request),
        );
    }

    /**
     * @throws DtoException
     */
    public static function instantiateFromFile(UploadedFile $file): self
    {
        return new self(
            file: $file,
        );
    }
}
