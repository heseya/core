<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WishlistProductStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'uuid',
                'exists:products,id',
                Rule::unique('wishlist_products')
                    ->where(function (Builder $query) {
                        return $query
                            ->where([
                                ['user_id', Auth::id()],
                                ['deleted_at', null],
                            ]);
                    }),
            ],
        ];
    }
}
