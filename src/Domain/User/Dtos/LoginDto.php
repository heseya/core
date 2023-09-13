<?php

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class LoginDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255), Email]
        public string $email,
        #[Required, StringType, Max(255)]
        public string $password,
        #[StringType, Max(255), Nullable]
        public null|string|Optional $code,
    ) {
    }
}
