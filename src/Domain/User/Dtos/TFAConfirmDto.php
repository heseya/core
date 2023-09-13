<?php

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class TFAConfirmDto extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $code,
    ) {
    }
}
