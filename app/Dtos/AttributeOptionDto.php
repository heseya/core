<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\AttributeOptionRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class AttributeOptionDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public function __construct(
        public readonly Missing|string|null $id,
        public readonly Missing|string|null $name,
        public readonly float|Missing|null $value_number,
        public readonly Missing|string|null $value_date,
        public readonly array|Missing $metadata,
    ) {}

    /**
     * @throws DtoException
     */
    public static function instantiateFromRequest(AttributeOptionRequest|FormRequest $request): self
    {
        return new self(
            id: $request->input('id', new Missing()),
            name: $request->input('name', new Missing()),
            value_number: $request->input('value_number', new Missing()),
            value_date: $request->input('value_date', new Missing()),
            metadata: self::mapMetadata($request),
        );
    }

    /**
     * @throws DtoException
     */
    public static function fromDataArray(array $data): self
    {
        return new self(
            id: array_key_exists('id', $data) ? $data['id'] : null,
            name: array_key_exists('name', $data) ? $data['name'] : null,
            value_number: array_key_exists('value_number', $data) ? $data['value_number'] : null,
            value_date: array_key_exists('value_date', $data) ? $data['value_date'] : null,
            metadata: self::mapMetadataFromArray($data),
        );
    }
}
