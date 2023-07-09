<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\FavouriteProductSetStoreRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;

class FavouriteProductSetDto extends Dto implements InstantiateFromRequest
{
    private string $product_set_id;

    public static function instantiateFromRequest(FavouriteProductSetStoreRequest|FormRequest $request): self
    {
        return new self(
            product_set_id: $request->input('product_set_id'),
        );
    }

    public function getProductSetId(): string
    {
        return $this->product_set_id;
    }
}
