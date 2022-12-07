<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class MetadataPersonalListDto extends Dto implements InstantiateFromRequest
{
    private array $metadata;

    public static function instantiateFromRequest(FormRequest|Request $request): self
    {
        $metadata = [];
        foreach ($request->all() as $key => $value) {
            $metadata[] = MetadataPersonalDto::manualInit(name: $key, value: $value);
        }

        return new self(
            metadata: $metadata,
        );
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
