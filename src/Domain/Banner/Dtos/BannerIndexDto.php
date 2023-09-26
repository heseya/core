<?php

declare(strict_types=1);

namespace Domain\Banner\Dtos;

use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class BannerIndexDto extends Data
{
    /**
     * @param string|Optional|null $slug
     * @param string[]|Optional|null $metadata
     * @param string[]|Optional|null $metadata_private
     * @param string[]|Optional $ids
     * @param bool|Optional $with_translations
     */
    public function __construct(
        #[StringType, Nullable, Max(255), AlphaDash]
        public readonly Optional|string|null $slug,
        #[ArrayType, Nullable]
        public readonly array|Optional|null $metadata,
        #[ArrayType, Nullable]
        public readonly array|Optional|null $metadata_private,
        #[ArrayType]
        public readonly array|Optional $ids,
        #[BooleanType]
        public readonly bool|Optional $with_translations = false,
    ) {}

    /**
     * @return array<string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'ids.*' => ['uuid'],
        ];
    }
}
