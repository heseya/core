<?php

declare(strict_types=1);

namespace Domain\Setting\Dtos;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class SettingUpdateDto extends Data
{
    public function __construct(
        #[StringType, Max(255), Unique('settings', 'name', ignore: new RouteParameterReference('setting'))]
        public Optional|string $name,
        #[StringType, Max(1000)]
        public Optional|string $value,
        #[BooleanType]
        public bool|Optional $public,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        /** @var array<string, mixed> $settingsArray */
        $settingsArray = Config::get('settings');
        /** @var Collection<string, mixed> $settings */
        $settings = Collection::make($settingsArray);

        /** @var string $currentSetting */
        $currentSetting = request()->route('setting');

        return [
            'name' => [
                Rule::notIn(
                    $settings->except([$currentSetting])->keys()->toArray(),
                ),
            ],
        ];
    }
}
