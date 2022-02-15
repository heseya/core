<?php

namespace App\Dtos;

use App\Http\Requests\AttributeOptionRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;

class AttributeOptionDto extends Dto
{
    private string $value_text;
    private float|null $value;

    public static function fromFormRequest(AttributeOptionRequest $request): self
    {
        return new self(
            value_text: $request->input('value_text'),
            value: $request->input('value'),
        );
    }

    public static function fromDataArray(array $data): self
    {
        return new self(
            value_text: $data['value_text'],
            value: array_key_exists('value', $data) ? $data['value'] : new Missing(),
        );
    }

    public function getValueText(): string
    {
        return $this->value_text;
    }

    public function getValue(): float|null
    {
        return $this->value;
    }
}
