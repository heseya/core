<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Rules\ConsentsExists;
use App\Rules\RequiredConsents;
use App\Traits\DtoHasPhone;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ProfileUpdateDto extends Data
{
    use DtoHasPhone;

    #[Computed]
    public Optional|string|null $phone_country;
    #[Computed]
    public Optional|string|null $phone_number;

    /**
     * @param Optional|string $name
     * @param string|Optional $birthday_date
     * @param Optional|string $phone
     * @param array<string, bool>|Optional $consents
     */
    public function __construct(
        #[Nullable, StringType, Max(255)]
        public Optional|string $name,

        #[Nullable, Date, BeforeOrEqual('today')]
        public Optional|string $birthday_date,

        #[StringType]
        public Optional|string $phone,

        public Optional|PreferencesDto $preferences,

        #[Nullable, ArrayType]
        public array|Optional $consents = [],
    ) {
        $this->initializePhone();
    }

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
