<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\TFASetupRequest;
use Heseya\Dto\Dto;
use Illuminate\Foundation\Http\FormRequest;

class TFASetupDto extends Dto implements InstantiateFromRequest
{
    private string $type;

    public static function instantiateFromRequest(FormRequest|TFASetupRequest $request): self
    {
        return new self(
            type: $request->input('type'),
        );
    }

    public function getType(): string
    {
        return $this->type;
    }
}
