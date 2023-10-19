<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Illuminate\Foundation\Http\FormRequest;

class RoleSearchDto implements DtoContract, InstantiateFromRequest
{
    public function __construct(
        private ?string $search,
        private ?string $name,
        private ?string $description,
        private ?bool $assignable,
        private ?array $metadata,
        private ?array $metadata_private,
        private ?array $ids,
        private ?bool $is_joinable,
    ) {}

    public function toArray(): array
    {
        $data = [];

        if ($this->getSearch() !== null) {
            $data['search'] = $this->getSearch();
        }

        if ($this->getName() !== null) {
            $data['name'] = $this->getName();
        }

        if ($this->getDescription() !== null) {
            $data['description'] = $this->getDescription();
        }

        if ($this->getAssignable() !== null) {
            $data['assignable'] = $this->getAssignable();
        }

        if ($this->getMetadata() !== null) {
            $data['metadata'] = $this->getMetadata();
        }

        if ($this->getMetadataPrivate() !== null) {
            $data['metadata_private'] = $this->getMetadataPrivate();
        }

        if ($this->getIds() !== null) {
            $data['ids'] = $this->getIds();
        }

        if ($this->getIsJoinable() !== null) {
            $data['is_joinable'] = $this->getIsJoinable();
        }

        return $data;
    }

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            $request->input('search', null),
            $request->input('name', null),
            $request->input('description', null),
            $request->has('assignable') ? $request->boolean('assignable') : null,
            $request->input('metadata', null),
            $request->input('metadata_private', null),
            $request->input('ids', null),
            $request->input('is_joinable', null),
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

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getMetadataPrivate(): ?array
    {
        return $this->metadata_private;
    }

    public function getIds(): ?array
    {
        return $this->ids;
    }

    public function getIsJoinable(): ?bool
    {
        return $this->is_joinable;
    }
}
