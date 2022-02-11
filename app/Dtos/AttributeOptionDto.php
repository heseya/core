<?php

namespace App\Dtos;

use App\Http\Requests\AttributeRequest;
use Heseya\Dto\Dto;

class AttributeOptionDto extends Dto
{
    private string $value_text;
    private float|null $value;

    public static function fromFormRequest(AttributeRequest $request)
    {
        return new self(
            value_text: $request->input('value_text'),
            value: $request->input('value')
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
