<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Illuminate\Http\Request;

class RoleSearchDto implements DtoContract, InstantiateFromRequest
{
    public function __construct(
        private ?string $search,
        private ?string $name,
        private ?string $description,
        private ?bool $assignable,
    ) {}

    public function toArray(): array
    {
        $data = [];

        if ($this->getSearch()) {
            $data['search'] = $this->getSearch();
        }

        if ($this->getName()) {
            $data['name'] = $this->getName();
        }

        if ($this->getDescription()) {
            $data['description'] = $this->getDescription();
        }

        if ($this->getAssignable()) {
            $data['assignable'] = $this->getAssignable();
        }

        return $data;
    }

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            $request->input('search', null),
            $request->input('name', null),
            $request->input('description', null),
            $request->input('assignable', null),
        );
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAssignable(): ?bool
    {
        return $this->assignable;
    }
}
