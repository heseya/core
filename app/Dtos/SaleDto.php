<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\SaleCreateRequest;
use App\Http\Requests\StatusUpdateRequest;
use App\Services\Contracts\DiscountStoreServiceContract;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

class SaleDto extends Dto implements InstantiateFromRequest
{
    protected string|Missing $name;
    protected string|null|Missing $description;
    protected float|Missing $value;
    protected string|Missing $type;
    protected int|Missing $priority;
    protected string|Missing $target_type;
    protected bool|Missing $target_is_allow_list;
    protected array|Missing $condition_groups;
    protected array|Missing $target_products;
    protected array|Missing $target_sets;
    protected array|Missing $target_shipping_methods;

    public static function instantiateFromRequest(FormRequest|SaleCreateRequest|StatusUpdateRequest $request): self
    {
        $conditionGroups = App::make(DiscountStoreServiceContract::class)
            ->mapConditionGroups($request->input('condition_groups', new Missing()));

        return new self(
            name: $request->input('name', new Missing()),
            description: $request->input('description', new Missing()),
            value: $request->input('value', new Missing()),
            type: $request->input('type', new Missing()),
            priority: $request->input('priority', new Missing()),
            target_type: $request->input('target_type', new Missing()),
            target_is_allow_list: $request->input('target_is_allow_list', new Missing()),
            condition_groups: $conditionGroups,
            target_products: $request->input('target_products', new Missing()),
            target_sets: $request->input('target_sets', new Missing()),
            target_shipping_methods: $request->input('target_shipping_methods', new Missing()),
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
}
