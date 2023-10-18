<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class OrganizationAcceptDto extends Data
{
    public function __construct(
        public readonly string $redirect_url,
        #[Uuid, Exists('sales_channels', 'id')]
        public readonly Optional|string $sales_channel_id,
    ) {}
}
