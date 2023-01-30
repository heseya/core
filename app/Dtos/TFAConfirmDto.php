<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\TFAConfirmRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;

class TFAConfirmDto extends Dto implements InstantiateFromRequest
{
    private string $code;

    public static function instantiateFromRequest(FormRequest|TFAConfirmRequest $request): self
    {
        return new self(
            code: $request->input('code'),
        );
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
