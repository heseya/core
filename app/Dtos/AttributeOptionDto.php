<?php

namespace App\Dtos;

use App\Http\Requests\AttributeOptionRequest;
use Heseya\Dto\Dto;

class AttributeOptionDto extends Dto
{
    private string|null $id;
    private string $name;
    private float|null $value_number;
    private string|null $value_date;

    public static function fromFormRequest(AttributeOptionRequest $request): self
    {
        return new self(
            id: $request->input('id'),
            name: $request->input('name'),
            value_number: $request->input('value_number'),
            value_date: $request->input('value_date'),
        );
    }

    public static function fromDataArray(array $data): self
    {
        return new self(
            id: array_key_exists('id', $data) ? $data['id'] : null,
            name: $data['name'],
            value_number: array_key_exists('value_number', $data) ? $data['value_number'] : null,
            value_date: array_key_exists('value_date', $data) ? $data['value_date'] : null,
        );
    }

    /**
     * @return string|null
     */
    public function getId(): string|null
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float|null
     */
    public function getValueNumber(): float|null
    {
        return $this->value_number;
    }

    /**
     * @return string|null
     */
    public function getValueDate(): string|null
    {
        return $this->value_date;
    }
}
