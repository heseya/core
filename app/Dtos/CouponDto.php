<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\CouponCreateRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Http\Requests\SaleCreateRequest;
use App\Http\Requests\StatusUpdateRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

final class CouponDto extends SaleDto implements InstantiateFromRequest
{
    use MapMetadata;

    protected Missing|string $code;
    protected array|Missing $metadata;

    public static function instantiateFromRequest(
        CouponCreateRequest|CouponUpdateRequest|FormRequest|SaleCreateRequest|StatusUpdateRequest $request,
    ): self {
        return new self(
            code: $request->input('code', new Missing()),
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

    public function getCode(): Missing|string
    {
        return $this->code;
    }
}
