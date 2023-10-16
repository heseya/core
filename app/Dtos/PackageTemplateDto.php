<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class PackageTemplateDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private Missing|string $name;
    private float|Missing $weight;
    private int|Missing $width;
    private int|Missing $height;
    private int|Missing $depth;

    private array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            weight: $request->input('weight', new Missing()),
            width: $request->input('width', new Missing()),
            height: $request->input('height', new Missing()),
            depth: $request->input('depth', new Missing()),
            metadata: self::mapMetadata($request),
        );
    }
}
