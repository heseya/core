<?php

declare(strict_types=1);

namespace Domain\App\Dtos;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AppIndexDto extends Data
{
    /**
     * @param string[]|Optional|null $metadata
     * @param string[]|Optional|null $metadata_private
     * @param string[]|Optional $ids
     */
    public function __construct(
        #[ArrayType, Nullable]
        public readonly array|Optional|null $metadata,
        #[ArrayType, Nullable]
        public readonly array|Optional|null $metadata_private,
        #[ArrayType]
        public readonly array|Optional $ids,
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
