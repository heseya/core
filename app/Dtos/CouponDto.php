<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\CouponCreateRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Http\Requests\SaleCreateRequest;
use App\Traits\MapMetadata;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

final class CouponDto extends SaleDto implements InstantiateFromRequest
{
    use MapMetadata;

    public readonly Missing|string $code;

    public function __construct(mixed ...$data)
    {
        $this->code = $data['code'];

        unset($data['code']);

        parent::__construct(...$data);
    }

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public static function instantiateFromRequest(
        CouponCreateRequest|CouponUpdateRequest|FormRequest|SaleCreateRequest $request
    ): self {
        $amounts = $request->has('amounts') ? array_map(
            fn ($data) => PriceDto::fromData(...$data),
            $request->input('amounts'),
        ) : new Missing();

        return new self(
            code: $request->input('code', new Missing()),
            metadata: self::mapMetadata($request),
            name: $request->input('name', new Missing()),
            slug: $request->input('slug', new Missing()),
            description: $request->input('description', new Missing()),
            description_html: $request->input('description_html', new Missing()),
            percentage: $request->input('percentage') ?? new Missing(),
            amounts: $amounts,
            priority: $request->input('priority', new Missing()),
            target_type: $request->input('target_type', new Missing()),
            target_is_allow_list: $request->input('target_is_allow_list', new Missing()),
            condition_groups: self::mapConditionGroups($request->input('condition_groups', new Missing())),
            target_products: $request->input('target_products', new Missing()),
            target_sets: $request->input('target_sets', new Missing()),
            target_shipping_methods: $request->input('target_shipping_methods', new Missing()),
            active: $request->input('active', new Missing()),
            seo: $request->has('seo') ? SeoMetadataDto::instantiateFromRequest($request) : new Missing(),
        );
    }
}
