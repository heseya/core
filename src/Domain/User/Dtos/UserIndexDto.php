<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class UserIndexDto extends Data
{
    /**
     * @param Optional|string[]|null $ids
     * @param Optional|string|null $name
     * @param Optional|string|null $email
     * @param Optional|int $limit
     * @param Optional|string $search
     * @param Optional|array<string, string>|null $metadata
     * @param Optional|array<string, string>|null $metadata_private
     * @param Optional|string|null $consent_name
     * @param Optional|string|null $consent_id
     * @param Optional|string[]|null $roles
     * @param string $sort
     * @param bool $full
     */
    public function __construct(
        #[Nullable, ArrayType]
        public array|Optional|null $ids,
        #[Nullable, StringType]
        public Optional|string|null $name,
        #[Nullable, StringType]
        public Optional|string|null $email,
        #[Nullable, IntegerType, Min(1)]
        public int|Optional $limit,
        #[Nullable, StringType]
        public Optional|string $search,
        #[Nullable, ArrayType]
        public array|Optional|null $metadata,
        #[Nullable, ArrayType]
        public array|Optional|null $metadata_private,
        #[Nullable, StringType]
        public Optional|string|null $consent_name,
        #[Nullable, StringType]
        public Optional|string|null $consent_id,
        #[Nullable, ArrayType]
        public array|Optional|null $roles,
        #[Nullable, StringType]
        public string $sort = 'created_at:asc',
        #[BooleanType]
        public bool $full = false,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'roles.*' => ['exists:roles,id'],
        ];
    }
}
