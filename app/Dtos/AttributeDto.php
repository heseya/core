<?php

namespace App\Dtos;

use App\Http\Requests\AttributeRequest;
use Heseya\Dto\Dto;

class AttributeDto extends Dto
{
    private string $name;
    private string $slug;
    private string $description;
    private string $type;
    private bool $global;
    private bool $sortable;
    private array $options;

    public static function fromFormRequest(AttributeRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            slug: $request->input('slug'),
            description: $request->input('description'),
            type: $request->input('type'),
            global: $request->input('global'),
            sortable: $request->input('sortable'),
            options: $request->has('options') ? array_map(
                fn ($data) => AttributeOptionDto::fromDataArray($data),
                $request->input('options')
            ) : [],
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
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getType(): string
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
     * @return bool
     */
    public function isSortable(): bool
    {
        return $this->sortable;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
