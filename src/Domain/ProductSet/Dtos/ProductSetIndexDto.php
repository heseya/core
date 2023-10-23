<?php

declare(strict_types=1);

namespace Domain\ProductSet\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class ProductSetIndexDto extends Data
{
    /**
     * @param string[]|Optional $metadata
     * @param string[]|Optional $metadata_private
     * @param string[]|Optional $ids
     */
    public function __construct(
        #[Max(255)]
        public Optional|string $search,
        #[Max(255)]
        public Optional|string $name,
        #[Max(255)]
        public Optional|string $slug,
        #[Uuid, Exists('product_sets', 'id')]
        public Optional|string $parent_id,
        public bool|Optional $public,
        public array|Optional $metadata,
        public array|Optional $metadata_private,
        public array|Optional $ids,

        #[Sometimes, Min(0), Max(500)]
        public int $depth = 0,
        public bool $root = false,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'ids.*' => ['uuid'],
        ];
    }
}
