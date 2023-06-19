<?php

namespace App\DTO;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Prohibits;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ProductSetIndexDto extends Data
{
    public function __construct(
        #[Max(255)]
        public string|Optional $search,
        #[Max(255)]
        public string|Optional $name,
        #[Max(255)]
        public string|Optional $slug,
        #[Uuid, Exists('product_sets', 'id')]
        public string|Optional $parent_id,
        public bool|Optional $public,
        public array|Optional $metadata,
        public array|Optional $metadata_private,
        public array|Optional $ids,

        /** @deprecated */
        #[Prohibits('depth')]
        public bool|Optional $tree,

        #[Sometimes, Prohibits('tree'), Min(0), Max(500)]
        public int $depth = 0,
        public bool $root = false,
    ) {
        // support for deprecated calls
        if ($tree === true) {
            $this->depth = 500;
        }
    }

    public static function rules(): array
    {
        return [
            'ids.*' => ['uuid'],
        ];
    }
}
