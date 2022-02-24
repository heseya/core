<?php

namespace App\Dtos;

use App\Http\Requests\AttributeOptionRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class AttributeOptionDto extends Dto
{
    private string|null|Missing $id;
    private string $name;
    private float|null|Missing $value_number;
    private string|null|Missing $value_date;

    public static function fromFormRequest(AttributeOptionRequest $request): self
    {
        return new self(
            id: $request->input('id', new Missing()),
            name: $request->input('name'),
            value_number: $request->input('value_number', new Missing()),
            value_date: $request->input('value_date', new Missing()),
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
     * @return string|Missing|null
     */
    public function getId(): string|null|Missing
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
     * @return float|Missing|null
     */
    public function getValueNumber(): float|null|Missing
    {
        return $this->value_number;
    }

    /**
     * @return string|Missing|null
     */
    public function getValueDate(): string|null|Missing
    {
        return $this->value_date;
    }
}
