<?php

namespace App\Dtos;

use App\Http\Requests\TFASetupRequest;
use Heseya\Dto\Dto;

class TFASetupDto extends Dto
{
    private string $type;

    public static function fromFormRequest(TFASetupRequest $request): self
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
