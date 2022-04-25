<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\CouponIndexRequest;
use App\Http\Requests\SaleIndexRequest;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class CouponIndexDto extends SaleIndexDto implements InstantiateFromRequest
{
    protected string|Missing $code;

    public static function instantiateFromRequest(FormRequest|CouponIndexRequest|SaleIndexRequest $request): self
    {
        return new self(
            search: $request->input('search', new Missing()),
            description: $request->input('description', new Missing()),
            coupon: true,
            metadata: $request->input('metadata', new Missing()),
            metadata_private: $request->input('metadata_private', new Missing()),
            code: $request->input('code', new Missing()),
        );
    }

    public function getCode(): Missing|string
    {
        return $this->code;
    }
}
