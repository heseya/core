<?php

namespace App\Dtos;

use App\Http\Requests\AttributeRequest;
use Heseya\Dto\Dto;

class AttributeDto extends Dto
{
    private string $name;
    private string $description;
    private int $type;
    private bool $global;
    private array $options;

    public static function fromFormRequest(AttributeRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
            type: $request->input('type'),
            global: $request->input('global'),
            options: array_map(
                fn ($data) => AttributeOptionDto::fromDataArray($data),
                $request->input('options')
            ),
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->global;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
