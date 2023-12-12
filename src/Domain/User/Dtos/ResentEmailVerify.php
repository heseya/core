<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

final class ResentEmailVerify extends Data
{
    public function __construct(
        #[Exists('users', 'email')]
        public readonly string $email,
        #[Required, StringType, Url, Max(255)]
        public readonly string $email_verify_url,
    ) {}
}
