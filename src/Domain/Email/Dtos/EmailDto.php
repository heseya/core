<?php

declare(strict_types=1);

namespace Domain\Email\Dtos;

use Spatie\LaravelData\Data;

final class EmailDto extends Data
{
    public function __construct(
        public readonly string $title,
        public readonly string $receiver,
        public readonly string $body,
    ) {}
}
