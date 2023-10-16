<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Spatie\LaravelData\Data;

final class OrganizationAcceptDto extends Data
{
    public function __construct(
        public readonly string $redirect_url,
    ) {}
}
