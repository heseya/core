<?php

namespace App\Dtos;

use App\Http\Requests\TFAConfirmRequest;
use Heseya\Dto\Dto;

class TFAConfirmDto extends Dto
{
    private string $code;

    public static function fromFormRequest(TFAConfirmRequest $request): self
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
