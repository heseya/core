<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Rules\ConsentsExists;
use App\Rules\RequiredConsents;
use DateTime;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ProfileUpdateDto extends Data
{
    /**
     * @param Optional|string $name
     * @param DateTime|Optional $birthday_date
     * @param Optional|string $phone
     * @param array<string, bool>|Optional $consents
     */
    public function __construct(
        #[Nullable, StringType, Max(255)]
        public Optional|string $name,

        #[WithCast(DateTimeInterfaceCast::class)]
        #[Nullable, Date, BeforeOrEqual('today')]
        public DateTime|Optional $birthday_date,

        #[Nullable]
        public Optional|string $phone,

        #[Nullable, ArrayType]
        public array|Optional $consents,

        public PreferencesDto $preferences,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'phone' => ['phone:AUTO'],
            'consents' => [new RequiredConsents()],
            'consents.*' => ['boolean', new ConsentsExists()],
        ];
    }
}
