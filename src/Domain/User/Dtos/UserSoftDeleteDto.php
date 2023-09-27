<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Symfony\Contracts\Service\Attribute\Required;

final class UserSoftDeleteDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $password,
    ) {}
}
