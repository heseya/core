<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\CouponCreateRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Http\Requests\SaleCreateRequest;
use App\Http\Requests\StatusUpdateRequest;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class CouponDto extends SaleDto implements InstantiateFromRequest
{
    protected string|Missing $code;

    public static function instantiateFromRequest(
        FormRequest|CouponCreateRequest|CouponUpdateRequest|StatusUpdateRequest|SaleCreateRequest $request
    ): self {
        $conditionGroups = $request->input('condition_groups', new Missing());
        $conditionGroupDtos = [];
        if (!$conditionGroups instanceof Missing) {
            foreach ($conditionGroups as $conditionGroup) {
                array_push($conditionGroupDtos, ConditionGroupDto::fromArray($conditionGroup['conditions']));
            }
        }

        return new self(
            name: $request->input('name', new Missing()),
            description: $request->input('description', new Missing()),
            value: $request->input('value', new Missing()),
            type: $request->input('type', new Missing()),
            priority: $request->input('priority', new Missing()),
            target_type: $request->input('target_type', new Missing()),
            target_is_allow_list: $request->input('target_is_allow_list', new Missing()),
            condition_groups: $conditionGroups instanceof Missing ? $conditionGroups : $conditionGroupDtos,
            target_products: $request->input('target_products', new Missing()),
            target_sets: $request->input('target_sets', new Missing()),
            target_shipping_methods: $request->input('target_shipping_methods', new Missing()),
            code: $request->input('code', new Missing()),
        );
    }

    public function getCode(): Missing|string
    {
        return $this->code;
    }
}
