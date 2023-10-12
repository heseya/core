<?php

namespace Domain\Organization\Dtos;

use Spatie\LaravelData\Data;

class OrganizationAcceptDto extends Data
{
    public function __construct(
        public readonly string $redirect_url,
    ) {}
}
