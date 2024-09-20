<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class ShowResetPasswordFormDto extends Data
{
    public function __construct(
        #[StringType, Nullable, FromRouteParameter('token')]
        public ?string $token = null,

        #[StringType, Nullable, FromRouteParameter('email')]
        public ?string $email = null,
    ) {}
}
