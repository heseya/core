<?php

namespace Domain\Redirect\Dtos;

use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class RedirectIndexDto extends Data
{
    public function __construct(
        #[FromRouteParameter('enabled'), BooleanType]
        public bool|Optional $enabled,
    ) {}
}
