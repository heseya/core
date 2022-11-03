<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FavouriteProductSetStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_set_id' => [
                'required',
                'uuid',
                'exists:product_sets,id',
                Rule::unique('favourite_product_sets')
                    ->where(function (Builder $query) {
                        return $query->where([
                            ['user_type', Auth::user()::class],
                            ['user_id', Auth::id()],
                            ['deleted_at', null],
                            ['product_set_id', $this->product_set_id],
                        ]);
                    }),
            ],
        ];
    }
}
