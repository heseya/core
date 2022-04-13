<?php

namespace App\Dtos;

use App\Http\Requests\CouponIndexRequest;
use App\Http\Requests\SaleIndexRequest;
use Heseya\Dto\Missing;

class CouponIndexDto extends SaleIndexDto
{
    protected string|Missing $code;

    public static function fromFormRequest(CouponIndexRequest|SaleIndexRequest $request): self
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
