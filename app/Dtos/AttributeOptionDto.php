<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\AttributeOptionRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class AttributeOptionDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    private array|Missing $metadata;
    private string|null|Missing $id;
    private string|null|Missing $name;
    private float|null|Missing $value_number;
    private string|null|Missing $value_date;

    public static function instantiateFromRequest(FormRequest|AttributeOptionRequest $request): self
    {
        return new self(
            id: $request->input('id', new Missing()),
            name: $request->input('name', new Missing()),
            value_number: $request->input('value_number', new Missing()),
            value_date: $request->input('value_date', new Missing()),
            metadata: self::mapMetadata($request),
        );
    }

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

    public function getId(): string|null|Missing
    {
        return $this->id;
    }

    public function getName(): string|null|Missing
    {
        return $this->name;
    }

    public function getValueNumber(): float|null|Missing
    {
        return $this->value_number;
    }

    public function getValueDate(): string|null|Missing
    {
        return $this->value_date;
    }
}
