<?php

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

class ProfileUpdateDto extends Data
{
    public function __construct(
        #[Nullable, StringType, Max(255)]
        public string|Optional $name,

        #[WithCast(DateTimeInterfaceCast::class)]
        #[Nullable, Date, BeforeOrEqual('today')]
        public DateTime|Optional $birthday_date,

        #[Nullable]
        public string|Optional $phone,

        #[Nullable, ArrayType]
        public array|Optional $consents,

        public PreferencesDto $preferences,
    ) {
    }

    public static function rules(ValidationContext $context): array
    {
        return [
            'phone' => ['phone:AUTO'],
            'consents' => [new RequiredConsents()],
            'consents.*' => ['boolean', new ConsentsExists()],
        ];
    }
}
