<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;

class AuthProviderMergeAccountDto extends Dto implements InstantiateFromRequest
{
    private string $merge_token;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            merge_token: $request->input('merge_token'),
        );
    }

    public function getMergeToken(): string
    {
        return $this->merge_token;
    }
}
