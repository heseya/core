<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Enums\MetadataType;
use Domain\Metadata\Dtos\MetadataPersonalDto;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class MetadataPersonalListDto extends Dto implements InstantiateFromRequest
{
    public array $metadata;

    /**
     * @throws DtoException
     */
    public static function instantiateFromRequest(FormRequest|Request $request): self
    {
        $metadata = [];
        foreach ($request->all() as $key => $value) {
            $metadata[] = new MetadataPersonalDto($key, $value, MetadataType::matchType($value));
        }

        return new self(
            metadata: $metadata,
        );
    }
}
