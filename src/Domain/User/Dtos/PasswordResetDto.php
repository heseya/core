<?php

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class PasswordResetDto extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $email,
        #[Required, Url]
        public string $redirect_url,
    ) {
    }
}
