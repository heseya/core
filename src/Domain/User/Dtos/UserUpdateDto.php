<?php

declare(strict_types=1);

namespace Domain\User\Dtos;

use App\Enums\RoleType;
use App\Traits\DtoHasPhone;
use App\Traits\MapMetadata;
use Heseya\Dto\Missing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BeforeOrEqual;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class UserUpdateDto extends Data
{
    use DtoHasPhone;
    use MapMetadata;

    #[Computed]
    public string|Optional|null $phone_country;
    #[Computed]
    public string|Optional|null $phone_number;
    /** @var array<string,string>|Missing */
    #[Computed]
    public array|Missing $metadata;

    /**
     * @param Optional|string|null $name
     * @param Optional|string|null $email
     * @param string[]|Optional $roles
     * @param Optional|string|null $birthday_date
     * @param Optional|string|null $phone
     */
    public function __construct(
        #[Nullable, Required, StringType, Max(255)]
        public Optional|string|null $name,
        #[Nullable, Required, Email, Max(255)]
        public Optional|string|null $email,
        #[ArrayType]
        public array|Optional $roles,
        #[Nullable, Date, StringType, BeforeOrEqual('now')]
        public Optional|string|null $birthday_date,
        #[Nullable, StringType]
        public Optional|string|null $phone,
    ) {
        $this->initializePhone();
        $this->metadata = self::mapMetadata(request());
    }

    /**
     * @return array<string, array<int,mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'email' => [Rule::unique('users')->whereNull('deleted_at')],
            'password' => [Password::defaults()],
            'roles.*' => [
                'uuid',
                Rule::exists('roles', 'id')->where(
                    fn (Builder $query) => $query->whereNotIn(
                        'type',
                        [RoleType::AUTHENTICATED->value, RoleType::UNAUTHENTICATED->value]
                    )
                ),
            ],
            'phone' => ['phone:AUTO'],
        ];
    }
}
