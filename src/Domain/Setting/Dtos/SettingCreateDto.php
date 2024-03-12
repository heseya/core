<?php

declare(strict_types=1);

namespace Domain\Setting\Dtos;

use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class SettingCreateDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255), Unique('settings', 'name')]
        public string $name,
        #[Required, StringType, Max(1000)]
        public string $value,
        #[Required, BooleanType]
        public bool $public,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => [Rule::notIn(array_keys(Config::get('settings')))],
        ];
    }
}
