<?php

namespace App\Dtos;

use App\Http\Requests\ProductSetProductReorderRequest;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

final class ProductsReorderDto extends Dto
{
    protected array|Missing $products;

    public static function instantiateFromRequest(
        FormRequest|ProductSetProductReorderRequest $request
    ): self {
        return new self(
            products: $request->input('products', new Missing()),
        );
    }

    public function getProducts(): array|Missing
    {
        return $this->products;
    }
}
