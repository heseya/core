<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\MediaStoreRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class MediaDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public array|Missing $metadata;
    private UploadedFile|Missing|null $file;
    private string|null|Missing $alt;
    private string|null|Missing $slug;

    public static function instantiateFromRequest(FormRequest|MediaStoreRequest $request): self
    {
        return new self(
            alt: $request->input('alt', new Missing()),
            file: $request->file('file', new Missing()),
            slug: $request->input('slug', new Missing()),
            metadata: self::mapMetadata($request),
        );
    }

    public static function instantiateFromFile(UploadedFile $file): self
    {
        return new self(
            file: $file,
        );
    }

    public function getAlt(): string|null|Missing
    {
        return $this->alt;
    }

    public function getFile(): UploadedFile|Missing|null
    {
        return $this->file;
    }

    public function getSlug(): string|null|Missing
    {
        return $this->slug;
    }
}
