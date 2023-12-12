<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class VerifyEmailDto extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $token,
    ) {}
}
