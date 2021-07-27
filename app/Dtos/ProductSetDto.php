<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;
use App\Dtos\Contracts\InstantiateFromRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductSetDto implements DtoContract, InstantiateFromRequest
{
    private string $name;
    private ?string $slug;
    private ?string $override_slug;
    private bool $public;
    private bool $hide_on_index;
    private ?string $parent_id;
    private Collection $children_ids;

    public function __construct(
        string $name,
        ?string $slug,
        ?string $override_slug,
        bool $public,
        bool $hide_on_index,
        ?string $parent_id,
        array $children_ids
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->override_slug = $override_slug;
        $this->public = $public;
        $this->hide_on_index = $hide_on_index;
        $this->parent_id = $parent_id;
        $this->children_ids = Collection::make($children_ids);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'override_slug' => $this->getOverrideSlug(),
            'public' => $this->isPublic(),
            'hide_on_index' => $this->isHiddenOnIndex(),
            'parent_id' => $this->getParentId(),
            'children_ids' => $this->getChildrenIds(),
        ];
    }

    public static function instantiateFromRequest(Request $request): self
    {
        return new self(
            $request->input('name'),
            $request->input('slug'),
            $request->input('override_slug'),
            $request->boolean('public', true),
            $request->boolean('hide_on_index', false),
            $request->input('parent_id', null),
            $request->input('children_ids', []),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getOverrideSlug(): ?string
    {
        return $this->override_slug;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function isHiddenOnIndex(): bool
    {
        return $this->hide_on_index;
    }

    public function getParentId(): ?string
    {
        return $this->parent_id;
    }

    public function getChildrenIds(): Collection
    {
        return $this->children_ids;
    }
}
