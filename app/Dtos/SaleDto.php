<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\SaleCreateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class SaleDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public function __construct(
        readonly public array $translations,
        readonly public array $published,
        readonly public Missing|string|null $slug,
        readonly public float|Missing $value,
        readonly public Missing|string $type,
        readonly public int|Missing $priority,
        readonly public Missing|string $target_type,
        readonly public bool|Missing $target_is_allow_list,
        readonly public array|Missing $condition_groups,
        readonly public array|Missing $target_products,
        readonly public array|Missing $target_sets,
        readonly public array|Missing $target_shipping_methods,
        readonly public bool|Missing $active,

        readonly public array|Missing $metadata,
        readonly public Missing|SeoMetadataDto $seo,
    ) {}

    public static function instantiateFromRequest(FormRequest|SaleCreateRequest $request): self
    {
        return new self(
            translations: $request->input('translations', []),
            published: $request->input('published', []),
            slug: $request->input('slug', new Missing()),
            value: $request->input('value', new Missing()),
            type: $request->input('type', new Missing()),
            priority: $request->input('priority', new Missing()),
            target_type: $request->input('target_type', new Missing()),
            target_is_allow_list: $request->input('target_is_allow_list', new Missing()),
            condition_groups: self::mapConditionGroups($request->input('condition_groups', new Missing())),
            target_products: $request->input('target_products', new Missing()),
            target_sets: $request->input('target_sets', new Missing()),
            target_shipping_methods: $request->input('target_shipping_methods', new Missing()),
            active: $request->input('active', new Missing()),
            metadata: self::mapMetadata($request),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
        );
    }

    public function getValue(): float|Missing
    {
        return $this->value;
    }

    public function getType(): Missing|string
    {
        return $this->type;
    }

    public function getPriority(): int|Missing
    {
        return $this->priority;
    }

    public function getTargetType(): Missing|string
    {
        return $this->target_type;
    }

    public function getTargetIsAllowList(): bool|Missing
    {
        return $this->target_is_allow_list;
    }

    public function getConditionGroups(): array|Missing
    {
        return $this->condition_groups;
    }

    public function getTargetProducts(): array|Missing
    {
        return $this->target_products;
    }

    public function getTargetSets(): array|Missing
    {
        return $this->target_sets;
    }

    public function getTargetShippingMethods(): array|Missing
    {
        return $this->target_shipping_methods;
    }

    public function getActive(): bool|Missing
    {
        return $this->active;
    }

    public function getSeo(): Missing|SeoMetadataDto
    {
        return $this->seo;
    }

    protected static function mapConditionGroups(array|Missing $conditionGroups): array|Missing
    {
        if ($conditionGroups instanceof Missing) {
            return $conditionGroups;
        }

        $conditionGroupDtos = [];

        foreach ($conditionGroups as $conditionGroup) {
            $conditionGroupDtos[] = ConditionGroupDto::fromArray($conditionGroup['conditions']);
        }

        return $conditionGroupDtos;
    }
}
