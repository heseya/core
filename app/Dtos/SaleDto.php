<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\SaleCreateRequest;
use App\Http\Requests\StatusUpdateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class SaleDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    protected string|Missing $name;
    protected string|null|Missing $slug;
    protected string|null|Missing $description;
    protected string|null|Missing $description_html;
    protected float|Missing $value;
    protected string|Missing $type;
    protected int|Missing $priority;
    protected string|Missing $target_type;
    protected bool|Missing $target_is_allow_list;
    protected array|Missing $condition_groups;
    protected array|Missing $target_products;
    protected array|Missing $target_sets;
    protected array|Missing $target_shipping_methods;
    protected bool|Missing $active;

    protected array|Missing $metadata;
    protected SeoMetadataDto|Missing $seo;

    public static function instantiateFromRequest(FormRequest|SaleCreateRequest|StatusUpdateRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            slug: $request->input('slug', new Missing()),
            description: $request->input('description', new Missing()),
            description_html: $request->input('description_html', new Missing()),
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

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getDescription(): Missing|string|null
    {
        return $this->description;
    }

    public function getValue(): float|Missing
    {
        return $this->value;
    }

    public function getType(): Missing|string
    {
        return $this->type;
    }

    public function getPriority(): Missing|int
    {
        return $this->priority;
    }

    public function getTargetType(): Missing|string
    {
        return $this->target_type;
    }

    public function getTargetIsAllowList(): Missing|bool
    {
        return $this->target_is_allow_list;
    }

    public function getConditionGroups(): Missing|array
    {
        return $this->condition_groups;
    }

    public function getTargetProducts(): Missing|array
    {
        return $this->target_products;
    }

    public function getTargetSets(): Missing|array
    {
        return $this->target_sets;
    }

    public function getTargetShippingMethods(): Missing|array
    {
        return $this->target_shipping_methods;
    }

    public function getActive(): Missing|bool
    {
        return $this->active;
    }

    public function getSeo(): SeoMetadataDto|Missing
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
