<?php

declare(strict_types=1);

namespace Domain\Organization\Dtos;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class OrganizationInviteDto extends Data
{
    /**
     * @param array<int, string> $emails
     */
    public function __construct(
        public readonly array $emails,
        public readonly string $redirect_url,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'emails.*' => ['email'],
        ];
    }
}
