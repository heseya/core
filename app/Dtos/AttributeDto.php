<?php

namespace App\Dtos;

use App\Http\Requests\AttributeRequest;
use Heseya\Dto\Dto;

class AttributeDto extends Dto
{
    private string $name;
    private string $description;
    private int $type;
    private bool $searchable;
    private array $options;

    public static function fromFormRequest(AttributeRequest $request)
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
            type: $request->input('type'),
            searchable: $request->input('searchable'),
            options: $request->input('options'),
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
    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
